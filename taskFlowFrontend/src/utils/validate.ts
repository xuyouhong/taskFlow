export function isValidEmail(value: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
}

export function isValidPhone(value: string): boolean {
  return /^1[3-9]\d{9}$/.test(value)
}

export function isValidUsername(value: string): boolean {
  return /^[a-zA-Z0-9_]{3,50}$/.test(value)
}

export function isValidPassword(value: string): boolean {
  return value.length >= 6 && value.length <= 20
}

export function isExternal(path: string): boolean {
  return /^(https?:|mailto:|tel:)/.test(path)
}
