<?php

namespace Admin\Permission\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Admin\Permission\Models\AdminUser;
use Admin\Permission\Models\AdminLoginLog;
use Admin\Permission\Http\Requests\AdminLoginRequest;
use Admin\Permission\Services\CaptchaService;

class AdminAuthController extends AdminController
{
    protected $captchaService;

    public function __construct(CaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
    }

    public function login(AdminLoginRequest $request)
    {
        $ip          = $this->getClientIp($request);
        $userAgent   = $request->userAgent();
        $throttleKey = 'login:' . $ip;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->error("尝试次数过多，请 {$seconds} 秒后再试", 429);
        }

        $credentials = $request->only(['username', 'password']);
        $captchaKey  = $request->input('captcha_key');
        $captchaCode = $request->input('captcha_code');

        $user = AdminUser::withoutGlobalScopes()
            ->where('username', $credentials['username'])
            ->first();

        $isValid = $this->captchaService->validate($captchaKey, $captchaCode);
        $logData = compact('ip', 'userAgent', 'credentials');

        if (!$isValid) {
            RateLimiter::hit($throttleKey);
            $this->logLogin($logData, false, '验证码错误', $user?->hash_id);
            return $this->error('验证码错误');
        }

        if (!$user) {
            RateLimiter::hit($throttleKey);
            $this->logLogin($logData, false, '用户不存在');
            return $this->error('用户名或密码错误');
        }

        if ($user->deleted_at) {
            RateLimiter::hit($throttleKey);
            $this->logLogin($logData, false, '账户已被删除', $user->hash_id);
            return $this->error('用户名或密码错误');
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey);
            $this->logLogin($logData, false, '密码错误', $user->hash_id);
            return $this->error('用户名或密码错误');
        }

        if ($user->status !== 1) {
            RateLimiter::hit($throttleKey);
            $this->logLogin($logData, false, '账户已被禁用', $user->hash_id);
            return $this->error('账户已被禁用');
        }

        RateLimiter::clear($throttleKey);

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);

        Auth::login($user);
        $token = $user->createToken('admin-token')->plainTextToken;

        $this->logLogin($logData, true, '登录成功', $user->hash_id);

        return $this->success([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('sanctum.expiration') ?? 525600,
            'user'         => $user->toArray(),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $latestLogin = AdminLoginLog::where('user_id', $user->hash_id)
                ->orderBy('_seq', 'desc')
                ->first();

            if ($latestLogin) {
                $logoutTime     = now();
                $onlineDuration = $logoutTime->diffInSeconds($latestLogin->login_at, true);

                $latestLogin->update([
                    'logout_at'       => $logoutTime,
                    'online_duration' => $onlineDuration
                ]);
            }

            $user->currentAccessToken()->delete();
        }

        return $this->success([], '退出成功');
    }

    public function user(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->error('用户未登录', 401);
        }

        $user->load('roles.permissions');

        return $this->success($user->toArray());
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $token = $user->createToken('admin-token')->plainTextToken;

        return $this->success([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('sanctum.expiration') ?? 525600,
        ]);
    }

    public function captcha()
    {
        try {
            $result = app(CaptchaService::class)->generate();

            return $this->success([
                'captcha_key'   => $result['captcha_key'],
                'captcha_image' => $result['captcha_image'],
            ]);
        } catch (\Exception $e) {
            \Log::error('验证码生成异常: ' . $e->getMessage());

            $message = app()->environment(['local', 'development'])
                ? '验证码生成失败: ' . $e->getMessage()
                : '验证码生成失败';

            return $this->error($message, 500);
        }
    }

    private function logLogin(array $logData, bool $success, string $message = '', ?string $userId = null)
    {
        try {
            $userAgent = $logData['userAgent'];
            $ip        = $logData['ip'];
            $username  = $logData['credentials']['username'];

            $browser  = $this->getBrowser($userAgent);
            $os       = $this->getOS($userAgent);
            $device   = $this->getDevice($userAgent);
            $location = $this->getLocationFromIp($ip);

            AdminLoginLog::create([
                'user_id'    => $userId,
                'username'   => $username,
                'ip'         => $ip,
                'user_agent' => $userAgent,
                'browser'    => $browser,
                'os'         => $os,
                'device'     => $device,
                'country'    => $location['country'],
                'region'     => $location['region'],
                'city'       => $location['city'],
                'login_at'   => now(),
                'status'     => $success ? 1 : 0,
                'message'    => $message,
            ]);
        } catch (\Exception $e) {
            \Log::error('登录日志记录失败: ' . $e->getMessage());
        }
    }

    private function getLocationFromIp(string $ip): array
    {
        $location = ['country' => null, 'region' => null, 'city' => null];

        // 私有/保留 IP 视为本地访问
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['country' => '本地', 'region' => null, 'city' => null];
        }

        $services = [
            'ip-api' => fn($ip) => $this->parseIpApi($ip),
            '纯真IP' => fn($ip) => $this->parsePconline($ip),
            '淘宝IP' => fn($ip) => $this->parseTaobao($ip),
        ];

        $fallback = null;

        foreach ($services as $name => $parser) {
            try {
                $result = $parser($ip);
                if ($result && !empty($result['country'])) {
                    // 优先返回有省/市信息的结果
                    if (!empty($result['region']) && !empty($result['city'])) {
                        return $result;
                    }
                    // 保留只有国家信息的结果作为兜底
                    if ($fallback === null) {
                        $fallback = $result;
                    }
                }
            } catch (\Exception $e) {
                \Log::debug('IP解析异常', ['ip' => $ip, 'service' => $name]);
            }
        }

        return $fallback ?? $location;
    }

    private function parsePconline(string $ip): ?array
    {
        try {
            $response = \Http::timeout(3)->get('http://whois.pconline.com.cn/ipJson.jsp', [
                'ip'   => $ip,
                'json' => true
            ]);

            if ($response->successful()) {
                $body = mb_convert_encoding($response->body(), 'UTF-8', 'GBK');
                if (preg_match('/IPCallBack\((.*?)\);/', $body, $matches)) {
                    $data = json_decode($matches[1], true);
                    if ($data && empty($data['err']) && !empty($data['pro']) && !empty($data['city'])) {
                        return [
                            'country' => '中国',
                            'region'  => $data['pro'],
                            'city'    => $data['city'],
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::debug('纯真IP解析失败', ['ip' => $ip]);
        }
        return null;
    }

    private function parseIpApi(string $ip): ?array
    {
        try {
            $response = \Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields'   => 'status,country,regionName,city',
                'lang'     => 'zh-CN',
                'timezone' => 'Asia/Shanghai'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['status'] === 'success' && !empty($data['regionName']) && !empty($data['city'])) {
                    return [
                        'country' => $data['country'],
                        'region'  => $data['regionName'],
                        'city'    => $data['city'],
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::debug('ip-api解析失败', ['ip' => $ip]);
        }
        return null;
    }

    private function parseTaobao(string $ip): ?array
    {
        try {
            $response = \Http::timeout(3)->get('https://ip.taobao.com/outGetIpInfo', [
                'ip'        => $ip,
                'accessKey' => 'alibaba-inc'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['code']) && $data['code'] === 0) {
                    $ipInfo = $data['data'];
                    if (!empty($ipInfo['region']) && !empty($ipInfo['city'])) {
                        return [
                            'country' => $ipInfo['country'],
                            'region'  => $ipInfo['region'],
                            'city'    => $ipInfo['city'],
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::debug('淘宝IP解析失败', ['ip' => $ip]);
        }
        return null;
    }

    private function getBrowser($userAgent)
    {
        if (strpos($userAgent, 'MSIE') !== false) return 'Internet Explorer';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Opera') !== false) return 'Opera';
        return 'Unknown';
    }

    private function getOS($userAgent)
    {
        if (strpos($userAgent, 'Windows') !== false) return 'Windows';
        if (strpos($userAgent, 'Mac') !== false) return 'Mac OS';
        if (strpos($userAgent, 'Linux') !== false) return 'Linux';
        if (strpos($userAgent, 'Unix') !== false) return 'Unix';
        if (strpos($userAgent, 'Android') !== false) return 'Android';
        if (strpos($userAgent, 'iOS') !== false) return 'iOS';
        return 'Unknown';
    }

    private function getDevice($userAgent)
    {
        if (strpos($userAgent, 'Mobile') !== false) return 'Mobile';
        if (strpos($userAgent, 'Tablet') !== false) return 'Tablet';
        return 'Desktop';
    }

    private function getClientIp(Request $request): string
    {
        // 使用 Laravel 内置的 IP 获取机制，配合 TrustProxies 中间件使用
        // 避免无条件信任 X-Forwarded-For 等请求头导致 IP 欺骗
        return $request->ip();
    }
}
