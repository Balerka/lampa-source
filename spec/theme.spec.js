import {beforeEach, expect, test, vi} from 'vitest'

vi.mock('../src/core/storage/storage', () => ({default: {get: vi.fn()}}))
vi.mock('../src/core/api/api', () => ({default: {}}))
vi.mock('../src/core/lang', () => ({default: {}}))
vi.mock('../src/core/timer', () => ({default: {}}))

import Storage from '../src/core/storage/storage'
import Theme from '../src/core/theme'

let append

beforeEach(() => {
    vi.stubGlobal('window', {
        lampa_settings: {cub_domain: 'local.example'},
        location: {protocol: 'https:'}
    })
    vi.stubGlobal('localStorage', {getItem: vi.fn(() => null)})
    append = vi.fn()
    vi.stubGlobal('$', vi.fn(value => {
        if(value === '#cub-theme') return {remove: vi.fn()}
        if(value === 'body') return {append}
        return value
    }))
    Storage.get.mockImplementation(key => {
        if(key === 'cub_theme') return 'https://cub.rip/extensions/196'
        if(key === 'account') return {token: 'test-token'}
    })
})

test('saved themes use the configured domain before adding the account token', () => {
    Theme.init()

    expect(append).toHaveBeenCalledWith('<link rel="stylesheet" href="https://local.example/extensions/196?token=test-token" id="cub-theme">')
})

test('custom theme URLs remain unchanged', () => {
    Theme.set('https://themes.example/custom.css')

    expect(append).toHaveBeenCalledWith('<link rel="stylesheet" href="https://themes.example/custom.css?token=test-token" id="cub-theme">')
})
