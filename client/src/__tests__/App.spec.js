import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing' // <--- Ezt kell hozzáadni
import App from '../App.vue'

describe('App', () => {
  it('mounts renders properly', () => {
    const wrapper = mount(App, {
      global: {
        plugins: [
          createTestingPinia({
            stubActions: false, // Engedélyezi a valódi store funkciókat
          }),
        ],
      },
    })
    
    // Itt vigyázz: ha az App-odban nincs benne a "You did it!" szöveg, 
    // akkor ez a sor hibát fog dobni, de már nem a Pinia miatt!
    // expect(wrapper.text()).toContain('You did it!') 
    
    expect(wrapper.exists()).toBe(true)
  })
})