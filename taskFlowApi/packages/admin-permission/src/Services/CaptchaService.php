<?php

namespace Admin\Permission\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CaptchaService
{
    /**
     * 生成验证码并返回 key + image/svg。
     */
    public function generate(): array
    {
        $driver = config('permission.captcha.driver', 'math');

        return $driver === 'svg'
            ? $this->generateSvgCaptcha()
            : $this->generateMathCaptcha();
    }

    /**
     * 数学运算验证码（GD 图像）。
     */
    protected function generateMathCaptcha(): array
    {
        try {
            [$expression, $result] = $this->generateMathExpression();

            $key = $this->generateKey();
            $this->cacheResult($key, $result);

            $image = $this->renderMathImage($expression);

            return [
                'captcha_key'   => $key,
                'captcha_image' => $image,
            ];
        } catch (\Exception $e) {
            Log::error('Captcha generation failed: ' . $e->getMessage());

            // 降级为纯文本验证码
            return $this->generateTextFallback();
        }
    }

    /**
     * SVG 验证码（纯矢量，无需 GD）。
     */
    protected function generateSvgCaptcha(): array
    {
        $charset = config('permission.captcha.charset', '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz');
        $length  = (int)config('permission.captcha.length', 4);

        $code = '';
        $max  = strlen($charset) - 1;
        for ($i = 0; $i < $length; $i ++) {
            $code .= $charset[random_int(0, $max)];
        }

        $key = $this->generateKey();
        $this->cacheResult($key, $code);

        $svg = $this->renderSvg($code);

        return [
            'captcha_key'   => $key,
            'captcha_image' => $svg,
        ];
    }

    /**
     * 验证验证码。
     */
    public function validate(string $key, mixed $code): bool
    {
        if (empty($key) || empty($code)) {
            return false;
        }

        $cacheKey   = $this->getCacheKey($key);
        $cachedCode = Cache::get($cacheKey);

        if ($cachedCode === null) {
            return false;
        }

        // 立即清除缓存，防止重放攻击
        Cache::forget($cacheKey);

        $input = trim((string)$code);

        if (!config('permission.captcha.case_sensitive', false)) {
            $cachedCode = strtolower($cachedCode);
            $input      = strtolower($input);
        }

        return $cachedCode === $input;
    }

    /**
     * 生成数学运算表达式和结果。
     */
    protected function generateMathExpression(): array
    {
        $max       = (int)config('permission.captcha.max_number', 99);
        $operators = ['+', '-', '*'];

        $op = $operators[random_int(0, 2)];

        switch ($op) {
            case '+':
                $a      = random_int(1, min($max - 1, 98));
                $b      = random_int(1, min($max - $a, 99 - $a));
                $result = $a + $b;
                break;
            case '-':
                $a      = random_int(2, min($max, 99));
                $b      = random_int(1, $a - 1);
                $result = $a - $b;
                break;
            case '*':
                $a      = random_int(1, min(9, $max));
                $b      = random_int(1, min(intval(99 / max($a, 1)), 9));
                $result = $a * $b;
                break;
            default:
                $a      = random_int(1, min($max - 1, 98));
                $b      = random_int(1, min($max - $a, 99 - $a));
                $result = $a + $b;
        }

        return ["{$a} {$op} {$b} = ?", (string)$result];
    }

    /**
     * 使用 GD 绘制数学运算验证码图像。
     */
    protected function renderMathImage(string $text): string
    {
        $width  = (int)config('permission.captcha.width', 280);
        $height = (int)config('permission.captcha.height', 80);

        $image = imagecreatetruecolor($width, $height);

        // 浅灰背景
        $bg = imagecolorallocate($image, 245, 245, 245);
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        // 干扰线
        for ($i = 0; $i < 4; $i ++) {
            $lineColor = imagecolorallocate(
                $image,
                random_int(180, 220),
                random_int(180, 220),
                random_int(180, 220)
            );
            imageline(
                $image,
                random_int(0, $width), random_int(0, $height),
                random_int(0, $width), random_int(0, $height),
                $lineColor
            );
        }

        // 噪点
        for ($i = 0; $i < 100; $i ++) {
            $dotColor = imagecolorallocate(
                $image,
                random_int(180, 230),
                random_int(180, 230),
                random_int(180, 230)
            );
            imagesetpixel(
                $image,
                random_int(0, $width - 1),
                random_int(0, $height - 1),
                $dotColor
            );
        }

        // 优先使用 TTF 字体渲染
        $fontPath = $this->resolveFontPath();
        if ($fontPath) {
            $this->renderWithTtfFont($image, $text, $fontPath, $width, $height);
        } else {
            $this->renderWithBuiltinFont($image, $text, $width, $height);
        }

        // 转换为 Base64
        ob_start();
        imagepng($image);
        $data = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($data);
    }

    /**
     * 搜索系统中可用的 TTF 字体文件。
     */
    protected function resolveFontPath(): ?string
    {
        // 优先使用配置指定的字体
        $configured = config('permission.captcha.font_path', '');
        if ($configured && file_exists($configured)) {
            return $configured;
        }

        // 项目内 resources/fonts 目录
        $projectPaths = [
            resource_path('fonts/Arial.ttf'),
            resource_path('fonts/DejaVuSans.ttf'),
            base_path('resources/fonts/Arial.ttf'),
        ];

        // 常见系统字体路径（macOS / Linux）
        $systemPaths = [
            // macOS
            '/System/Library/Fonts/Supplemental/Arial.ttf',
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
            '/Library/Fonts/Arial Unicode.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
            '/System/Library/Fonts/PingFang.ttc',
            // Linux (Debian/Ubuntu/CentOS)
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
            '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
        ];

        foreach (array_merge($projectPaths, $systemPaths) as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * 使用 TTF 字体渲染验证码文本（大字体，逐字符随机旋转/着色）。
     */
    protected function renderWithTtfFont($image, string $text, string $fontPath, int $width, int $height): void
    {
        $fontSize = (int)config('permission.captcha.font_size', 32);
        $len      = strlen($text);

        // 计算每个字符的宽度
        $charWidth  = floor($width / max($len + 1, 2));
        $totalWidth = $charWidth * $len;
        $startX     = (int)(($width - $totalWidth) / 2);

        for ($i = 0; $i < $len; $i ++) {
            $char  = $text[$i];
            $x     = $startX + $charWidth * $i + random_int(0, 4);
            $y     = (int)($height / 2 + $fontSize / 3 + random_int(- 4, 4));
            $angle = random_int(- 15, 15);

            // 深色随机颜色
            $color = imagecolorallocate(
                $image,
                random_int(20, 80),
                random_int(20, 80),
                random_int(20, 80)
            );

            // 阴影
            $shadowColor = imagecolorallocate($image, 200, 200, 200);
            imagettftext($image, $fontSize, $angle, $x + 1, $y + 1, $shadowColor, $fontPath, $char);
            imagettftext($image, $fontSize, $angle, $x, $y, $color, $fontPath, $char);
        }
    }

    /**
     * 使用 GD 内置字体渲染（TTF 不可用时的降级方案，通过缩放放大文字）。
     */
    protected function renderWithBuiltinFont($image, string $text, int $width, int $height): void
    {
        $font  = 5;
        $fontW = imagefontwidth($font);
        $fontH = imagefontheight($font);

        // 计算缩放倍数，使文字高度约占图片高度的 60%
        $targetH = (int)($height * 0.6);
        $scale   = max(2, (int)floor($targetH / $fontH));

        $textW   = $fontW * strlen($text);
        $scaledW = $textW * $scale;
        $scaledH = $fontH * $scale;

        // 创建临时画布，绘制原始文字后缩放
        $tmp   = imagecreatetruecolor($textW + 4, $fontH + 4);
        $tmpBg = imagecolorallocate($tmp, 245, 245, 245);
        imagefilledrectangle($tmp, 0, 0, $textW + 4, $fontH + 4, $tmpBg);

        $textColor = imagecolorallocate($tmp, 50, 50, 50);
        imagestring($tmp, $font, 2, 2, $text, $textColor);

        // 缩放到目标尺寸
        $scaled = imagecreatetruecolor($scaledW, $scaledH);
        imagecopyresampled($scaled, $tmp, 0, 0, 2, 2, $scaledW, $scaledH, $textW, $fontH);
        imagedestroy($tmp);

        // 合并到主画布居中位置
        $x = (int)(($width - $scaledW) / 2);
        $y = (int)(($height - $scaledH) / 2);
        imagecopy($image, $scaled, $x, $y, 0, 0, $scaledW, $scaledH);
        imagedestroy($scaled);
    }

    /**
     * 生成 SVG 验证码。
     */
    protected function renderSvg(string $code): string
    {
        $width  = (int)config('permission.captcha.width', 280);
        $height = (int)config('permission.captcha.height', 80);
        $len    = strlen($code);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
        $svg .= '<rect width="100%" height="100%" fill="#f5f5f5"/>';

        // 干扰线
        for ($i = 0; $i < 3; $i ++) {
            $x1    = random_int(0, $width);
            $y1    = random_int(0, $height);
            $x2    = random_int(0, $width);
            $y2    = random_int(0, $height);
            $color = sprintf('#%02x%02x%02x', random_int(160, 210), random_int(160, 210), random_int(160, 210));
            $svg   .= "<line x1=\"{$x1}\" y1=\"{$y1}\" x2=\"{$x2}\" y2=\"{$y2}\" stroke=\"{$color}\" stroke-width=\"1\"/>";
        }

        // 字符渲染
        $charWidth = floor($width / max($len, 1));
        for ($i = 0; $i < $len; $i ++) {
            $char     = $code[$i];
            $x        = $charWidth * $i + random_int(5, 15);
            $y        = random_int($height * 0.4, $height * 0.75);
            $fontSize = random_int(24, 32);
            $rotate   = random_int(- 20, 20);
            $color    = sprintf('#%02x%02x%02x', random_int(30, 100), random_int(30, 100), random_int(30, 100));

            $svg .= "<text x=\"{$x}\" y=\"{$y}\" font-size=\"{$fontSize}\" fill=\"{$color}\" transform=\"rotate({$rotate}, {$x}, {$y})\" font-family=\"Arial, sans-serif\">{$char}</text>";
        }

        $svg .= '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * 纯文本降级方案。
     */
    protected function generateTextFallback(): array
    {
        [$expression, $result] = $this->generateMathExpression();
        $key = $this->generateKey();
        $this->cacheResult($key, $result);

        return [
            'captcha_key'   => $key,
            'captcha_image' => null,
            'expression'    => $expression,
            'note'          => 'Please solve: ' . $expression,
        ];
    }

    /**
     * 生成安全的缓存 Key。
     */
    protected function generateKey(): string
    {
        $uuid = Str::uuid()->toString();
        return hash_hmac('sha256', $uuid, config('app.key', ''));
    }

    /**
     * 缓存验证码结果。
     */
    protected function cacheResult(string $key, string $value): void
    {
        $expireMinutes = (int)config('permission.captcha.expire', 5);
        $cacheKey      = $this->getCacheKey($key);
        Cache::put($cacheKey, $value, now()->addMinutes($expireMinutes));
    }

    /**
     * 解析完整的缓存 Key。
     */
    protected function getCacheKey(string $key): string
    {
        return 'admin_captcha_' . $key;
    }
}
