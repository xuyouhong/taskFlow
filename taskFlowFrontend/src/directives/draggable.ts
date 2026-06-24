import type { Directive, DirectiveBinding } from 'vue'

interface DragState {
  startX: number
  startY: number
  startLeft: number
  startTop: number
  dragging: boolean
}

const dragStates = new WeakMap<HTMLElement, DragState>()

const onMousedown = (e: MouseEvent, el: HTMLElement) => {
  const target = e.target as HTMLElement
  if (target.closest('.el-dialog__headerbtn')) return

  const dialogEl = el.querySelector('.el-dialog') as HTMLElement
  if (!dialogEl) return

  const style = window.getComputedStyle(dialogEl)
  const marginLeft = parseFloat(style.marginLeft) || 0
  const offsetLeft = dialogEl.offsetLeft - marginLeft

  dragStates.set(el, {
    startX: e.clientX,
    startY: e.clientY,
    startLeft: offsetLeft,
    startTop: dialogEl.offsetTop,
    dragging: false,
  })

  const onMousemove = (e: MouseEvent) => {
    const state = dragStates.get(el)
    if (!state) return

    if (!state.dragging) {
      state.dragging = true
      dialogEl.style.position = 'relative'
      dialogEl.style.margin = '0 auto'
    }

    const dx = e.clientX - state.startX
    const dy = e.clientY - state.startY

    dialogEl.style.left = `${state.startLeft + dx}px`
    dialogEl.style.top = `${state.startTop + dy}px`
  }

  const onMouseup = () => {
    dragStates.delete(el)
    document.removeEventListener('mousemove', onMousemove)
    document.removeEventListener('mouseup', onMouseup)
    document.body.style.userSelect = ''
    document.body.style.cursor = ''
  }

  document.addEventListener('mousemove', onMousemove)
  document.addEventListener('mouseup', onMouseup)
  document.body.style.userSelect = 'none'
  document.body.style.cursor = 'move'
}

export const vDraggable: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    const headerEl = el.querySelector('.el-dialog__header') as HTMLElement
    if (headerEl) {
      headerEl.style.cursor = 'move'
      headerEl.addEventListener('mousedown', (e) => onMousedown(e, el))
    }
  },
  unmounted(el: HTMLElement) {
    const headerEl = el.querySelector('.el-dialog__header') as HTMLElement
    if (headerEl) {
      headerEl.removeEventListener('mousedown', (e) => onMousedown(e, el))
      headerEl.style.cursor = ''
    }
  },
}
