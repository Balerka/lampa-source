import {beforeEach, expect, test, vi} from 'vitest'

vi.mock('../src/core/storage/storage', () => ({default: {field: vi.fn(), set: vi.fn()}}))
vi.mock('../src/utils/utils', () => ({default: {checkHttp: value => /^https?:/.test(value) ? value : 'http://' + value}}))

import Storage from '../src/core/storage/storage'
import TMDB from '../src/core/tmdb/tmdb'
import Proxy from '../src/core/tmdb/proxy'

beforeEach(() => {
    vi.clearAllMocks()
    vi.stubGlobal('window', {lampa_settings: {account_domain: 'localhost:8000'}})
    Storage.field.mockImplementation(name => name == 'proxy_tmdb')
})

test('API and posters use the backend without CUB mirrors or account email', () => {
    expect(TMDB.api('movie/123?api_key=key&language=ru')).toBe('http://localhost:8000/api/tmdb/api/movie/123?api_key=key&language=ru')
    expect(TMDB.image('t/p/w500//poster.jpg')).toBe('http://localhost:8000/api/tmdb/image/t/p/w500/poster.jpg')
})

test('a separate backend URL is supported', () => {
    window.lampa_settings.tmdb_proxy_url = 'https://backend.example/api/tmdb/'
    expect(TMDB.api('movie/123')).toBe('https://backend.example/api/tmdb/api/movie/123')
})

test('disabling proxy uses official HTTPS endpoints', () => {
    Storage.field.mockReturnValue(false)
    expect(TMDB.api('movie/123')).toBe('https://api.themoviedb.org/3/movie/123')
    expect(TMDB.image('t/p/w500/poster.jpg')).toBe('https://image.tmdb.org/t/p/w500/poster.jpg')
})

test('automatic proxy activation keeps the backend URL functions', () => {
    Storage.field.mockReturnValue(true)
    Proxy.init()
    expect(Storage.set).toHaveBeenCalledWith('proxy_tmdb', true)
    expect(TMDB.api('movie/123')).toBe('http://localhost:8000/api/tmdb/api/movie/123')
})
