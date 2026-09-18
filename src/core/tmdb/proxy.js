import Storage from '../storage/storage.js'

function init(){
    if(Storage.field('proxy_tmdb_auto')) Storage.set('proxy_tmdb', true)
}

export default {
    init
}
