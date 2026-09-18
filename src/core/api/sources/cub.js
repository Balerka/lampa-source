import TMDB from './tmdb'

function category(params, oncomplite, onerror){
    if(params.url == 'anime'){
        params = Object.assign({}, params, {url: 'tv', genres: 16, orig_lang: 'ja'})
    }

    return TMDB.category(params, oncomplite, onerror)
}

function list(params, oncomplite, onerror){
    let url = params.url || ''

    if(url.charAt(0) == '?' || url.indexOf('top/') == 0){
        params = Object.assign({}, params, {
            url: 'discover/' + (/cat=tv|\/tv$/.test(url) ? 'tv' : 'movie')
        })
    }

    return TMDB.list(params, oncomplite, onerror)
}

function reactionsGet(params, oncomplite){
    oncomplite({result: []})
}

function unavailable(params, oncomplite, onerror){
    if(onerror) onerror()
}

function extensions(call){
    call({secuses: true, extensions: [], results: []})
}

export default {
    main: (...args)=>TMDB.main(...args),
    menu: (...args)=>TMDB.menu(...args),
    full: (...args)=>TMDB.full(...args),
    list,
    category,
    clear: (...args)=>TMDB.clear(...args),
    person: (...args)=>TMDB.person(...args),
    seasons: (...args)=>TMDB.seasons(...args),
    menuCategory: (...args)=>TMDB.menuCategory(...args),
    discovery: (...args)=>TMDB.discovery(...args),
    reactionsGet,
    reactionsAdd: unavailable,
    discussGet: unavailable,
    extensions
}
