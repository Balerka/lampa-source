import {beforeEach, expect, test, vi} from 'vitest'

vi.mock('../src/core/api/sources/tmdb', () => ({default: {
    main: vi.fn(), full: vi.fn(), category: vi.fn(), list: vi.fn()
}}))
vi.mock('../src/utils/utils', () => ({default: {
    checkHttp: value => /^https?:/.test(value) ? value : 'http://' + value
}}))

import TMDB from '../src/core/api/sources/tmdb'
import CUB from '../src/core/api/sources/cub'
import Backend from '../src/core/backend'
import Manifest from '../src/core/manifest'

beforeEach(() => {
    vi.clearAllMocks()
    vi.stubGlobal('window', {lampa_settings: {account_domain: 'https://local.example/'}})
    vi.stubGlobal('localStorage', {getItem: vi.fn(() => 'cub.rip')})
})

test('backend endpoints use the configured origin without duplicating protocol', () => {
    expect(Backend.url('reactions/get/movie_16221')).toBe('https://local.example/api/reactions/get/movie_16221')
    expect(Manifest.cub_site).toBe('local.example')
    expect(Manifest.cub_domain).toBe('local.example')
})

test('saved public mirrors cannot re-enable CUB fallback', () => {
    expect(Manifest.cub_mirrors).toEqual([])
    expect(Manifest.soc_mirrors).toEqual([])
})

test('old CUB cards load through TMDB', () => {
    const params = {id: 16221, method: 'movie', source: 'cub'}
    const complete = vi.fn()
    const error = vi.fn()
    CUB.full(params, complete, error)
    expect(TMDB.full).toHaveBeenCalledWith(params, complete, error)
})

test('reactions and discussions complete without network requests', () => {
    const complete = vi.fn()
    const error = vi.fn()
    CUB.reactionsGet({id: 16221, method: 'movie'}, complete)
    expect(complete).toHaveBeenCalledWith({result: []})
    CUB.discussGet({}, complete, error)
    expect(error).toHaveBeenCalledOnce()
})

test('legacy anime category uses TMDB filters', () => {
    CUB.category({url: 'anime'}, undefined, undefined)
    expect(TMDB.category).toHaveBeenCalledWith({url: 'tv', genres: 16, orig_lang: 'ja'}, undefined, undefined)
})

test('legacy CUB lists use TMDB discover', () => {
    CUB.list({url: '?cat=tv&sort=top', page: 2}, undefined, undefined)
    expect(TMDB.list).toHaveBeenCalledWith({url: 'discover/tv', page: 2}, undefined, undefined)
})
