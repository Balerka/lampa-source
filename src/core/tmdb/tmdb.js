import Storage from '../storage/storage'
import Backend from '../backend'

let broken_images = 0

function proxy(path){
    let settings = window.lampa_settings || {}
    let base = settings.tmdb_proxy_url || Backend.url('tmdb')

    return base.replace(/\/+$/, '') + '/' + path
}

/**
 * Проксировать API запрос
 * @param {string} url URL запроса
 * @return {string} Проксированный URL запроса
 */
function api(url){
    return Storage.field('proxy_tmdb') ? proxy('api/' + url) : 'https://api.themoviedb.org/3/' + url
}

/**
 * Проксировать изображение
 * @param {string} url URL изображения
 * @return {string} Проксированный URL изображения
 */
function image(url){
    url = url.replace(/\/+/g, '/')

    return Storage.field('proxy_tmdb') ? proxy('image/' + url) : 'https://image.tmdb.org/' + url
}

/**
 * Сообщить о сломанных изображениях
 */
function broken(){
    broken_images++

    if(broken_images > 50){
        broken_images = 0

        if(Storage.field('proxy_tmdb_auto')) Storage.set('proxy_tmdb', true)
    }
}

/**
 * Получить ключ TMDB
 * @return {string} Ключ TMDB
 */
function key(){
    return '4ef0d7355d9ffb5151e987764708ce96'
}

export default {
    api,
    key,
    image,
    broken
}
