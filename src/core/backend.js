import Utils from '../utils/utils'

function url(path = ''){
    let settings = window.lampa_settings || {}
    let domain = settings.account_domain || '127.0.0.1:8000'

    return Utils.checkHttp(domain).replace(/\/+$/, '') + '/api/' + path.replace(/^\/+/, '')
}

export default {url}
