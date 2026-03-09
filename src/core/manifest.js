let object = {
    author: 'Yumata',
    github: 'https://github.com/yumata/lampa-source',
    css_version: '3.1.6',
    app_version: '3.1.6',
    cub_site: 'cub.rip',
    apk_link_download: 'https://github.com/lampa-app/LAMPA/releases/download/v1.12.3/app-lite-release.apk'
}

let plugins = []
let settings = ()=> window.lampa_settings || {}

function readStringSetting(name, fallback){
    let value = settings()[name]

    return typeof value == 'string' && value.trim() ? value.trim() : fallback
}

function readArraySetting(name, fallback){
    let value = settings()[name]

    return Object.prototype.toString.call(value) === '[object Array]' && value.length ? value : fallback
}

Object.defineProperty(object, 'app_digital', { get: ()=> parseInt(object.app_version.replace(/\./g,'')) })
Object.defineProperty(object, 'css_digital', { get: ()=> parseInt(object.css_version.replace(/\./g,'')) })

Object.defineProperty(object, 'plugins', {
    get: ()=> plugins,
    set: (plugin)=> {
        if(typeof plugin == 'object' && typeof plugin.type == 'string'){
            plugins.push(plugin)
        }
    }
})

Object.defineProperty(object, 'account_service_name', {
    get: ()=> readStringSetting('account_service_name', 'CUB'),
    set: ()=> {}
})

Object.defineProperty(object, 'account_premium_name', {
    get: ()=> readStringSetting('account_premium_name', object.account_service_name + ' Premium'),
    set: ()=> {}
})

/**
 * Ссылка на GitHub с файлами приложения
 */
Object.defineProperty(object, 'github_lampa', {
    get: ()=> window.lampa_settings.fix_widget ? 'http://lampa.mx/' : 'https://yumata.github.io/lampa/',
    set: ()=> {}
})

/**
 * Старые зеркала, которые не используются больше, но могут быть полезны для обратной совместимости
 */
Object.defineProperty(object, 'old_mirrors', {
    get: ()=> ['cub.red', 'standby.cub.red', 'kurwa-bober.ninja', 'nackhui.com'],
    set: ()=> {}
})

/**
 * Список актуальных зеркал
 */
Object.defineProperty(object, 'cub_mirrors', {
    get: ()=> {
        let lampa = ['cub.rip', 'durex.monster', 'cubnotrip.top']
        let forced = readArraySetting('cub_mirrors', [])
        let users = localStorage.getItem('cub_mirrors') || '[]'

        try {
            users = JSON.parse(users)
        } catch (e) {
            users = []
        }

        if(forced.length){
            return forced
        }

        if(Object.prototype.toString.call( users ) === '[object Array]' && users.length){
            return lampa.concat(users)
        }

        return lampa
    },
    set: ()=> {}
})

/**
 * Список зеркал для сокета, вынесены отдельно, так как могут отличаться от обычных зеркал
 */
Object.defineProperty(object, 'soc_mirrors', {
    get: ()=> ['cub.red', 'kurwa-bober.ninja', 'nackhui.com'],
    set: ()=> {}
})

/**
 * Текущее доменное имя, которое используется для работы с CUB
 */
Object.defineProperty(object, 'cub_domain', {
    get: ()=> {
        let forced = readStringSetting('cub_domain', '')
        let use = localStorage.getItem('cub_domain') || ''

        if(forced && object.cub_mirrors.indexOf(forced) > -1){
            return forced
        }

        return object.cub_mirrors.indexOf(use) > -1 ? use : object.cub_mirrors[0]
    } 
})

Object.defineProperty(object, 'account_site', {
    get: ()=> readStringSetting('account_site', object.cub_site),
    set: ()=> {}
})

Object.defineProperty(object, 'account_domain', {
    get: ()=> readStringSetting('account_domain', object.cub_domain),
    set: ()=> {}
})

Object.defineProperty(object, 'account_assets_domain', {
    get: ()=> readStringSetting('account_assets_domain', object.cub_domain),
    set: ()=> {}
})

/**
 * Ссылка на сайт CUB
 */
Object.defineProperty(object, 'qr_site', { 
    get: ()=> {
        return object.account_assets_domain + '/img/other/qr-code-strong.png'
    } 
})

/**
 * Ссылка на QR для добавления устройства
 */
Object.defineProperty(object, 'qr_device_add', { 
    get: ()=> {
        return object.account_assets_domain + '/img/other/qr-add-device.png'
    } 
})

export default object
