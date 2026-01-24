import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'

const Dummy = {
    template: `<div data-testid="dummy">Frontend unit OK</div>`,
}

describe('unit smoke', () => {
    it('mounts a component', () => {
        const wrapper = mount(Dummy)
        expect(wrapper.get('[data-testid="dummy"]').text()).toBe('Frontend unit OK')
    })
})
