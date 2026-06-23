// 处理图片URL，将相对地址转换为完整的资源URL
export const handleImageUrl = (url: string | undefined | null): string => {
  if (!url) {
    return ''
  }

  // 如果已经是完整URL，直接返回
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url
  }

  // 如果是相对地址，添加资源服务器域名
  const uploadUrl = import.meta.env.VITE_UPLOAD_URL || ''

  // 确保URL格式正确，避免重复斜杠
  if (url.startsWith('/')) {
    return `${uploadUrl}${url.substring(1)}`
  }

  return `${uploadUrl}${url}`
}
