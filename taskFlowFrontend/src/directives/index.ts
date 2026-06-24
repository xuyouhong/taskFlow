import type { App } from 'vue'
import { permissionDirective } from './permission'
import { vDraggable } from './draggable'

export function setupDirectives(app: App) {
  app.directive('permission', permissionDirective)
  app.directive('draggable', vDraggable)
}
