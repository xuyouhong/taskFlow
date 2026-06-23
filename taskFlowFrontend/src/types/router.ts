import 'vue-router'

declare module 'vue-router' {
  interface RouteMeta {
    title?: string
    icon?: string
    hidden?: boolean
    noAuth?: boolean
    keepAlive?: boolean
    affix?: boolean
    type?: number
    activeMenu?: string
    breadcrumb?: boolean
  }
}
