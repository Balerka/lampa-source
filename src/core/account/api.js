import Permit from './permit'
import Utils from '../../utils/utils'
import Manifest from '../manifest'
import Reguest from '../../utils/reguest'
import Arrays from '../../utils/arrays'
import Storage from '../storage/storage'
import Modal from './modal'

let network = new Reguest()

let backendRoutes = [
    /^device\/add$/,
    /^device\/code\/manual$/,
    /^device\/code\/create$/,
    /^users\/get$/,
    /^profiles\/all$/,
    /^profiles\/create$/,
    /^notice\/all$/,
    /^person\/list$/,
    /^users\/backup\/import$/,
    /^users\/backup\/export$/,
    /^bookmarks\/dump$/,
    /^bookmarks\/changelog$/,
    /^bookmarks\/add$/,
    /^bookmarks\/remove$/,
    /^bookmarks\/clear$/,
    /^bookmarks\/sync$/,
    /^timeline\/dump$/,
    /^timeline\/changelog$/,
    /^timeline\/update$/,
    /^storage\/data\/[^/]+\/[^/]+$/,
    /^notifications\/all$/,
    /^notifications\/add$/
]

function url(){
    return Utils.protocol() + Manifest.account_domain + '/api/'
}

function sourceUrl(){
    return Utils.protocol() + Manifest.cub_site + '/api/'
}

function normalizePath(path = ''){
    return (path + '').replace(/^\/+/, '').split('?')[0]
}

function useBackend(path){
    let normalized = normalizePath(path)

    return backendRoutes.some((route)=> route.test(normalized))
}

function resolveUrl(path, params = {}){
    if(params.url) return params.url

    return (useBackend(path) ? url() : sourceUrl()) + path
}

function load(path, params = {}, post = false){
    return new Promise((resolve, reject)=>{
        if(Permit.token){
            let account = Permit.account

            Arrays.extend(params, {
                headers: {
                    token: account.token,
                    profile: account.profile.id
                },
                timeout: 8000
            })

            let u = resolveUrl(path, params)

            network.silent(u, resolve, reject, post, params)
        }
        else{
            reject({decode_code: 403})
        }
    })
}

function persons(secuses, error){
    if(Permit.access && !window.lampa_settings.disable_features.persons){
        load('person/list').then((data)=>{
            Storage.set('person_subscribes_id', data.results.map(a=>a.person_id))

            if(secuses) secuses(data.results)
        }).catch(error ? error : ()=>{})
    }
    else if(error) error({decode_code: 403})
}

function user(secuses, error){
    if(Permit.access){
        load('users/get').then((data)=>{
            Storage.set('account_user', JSON.stringify(data.user))

            if(secuses) secuses(data.user)
        }).catch(error ? error : ()=>{})
    }
    else if(error) error({decode_code: 403})
}

function plugins(call){
    call([])
}

function pluginToggle(plugin, status){
    return
}

function notices(call){
    if(Permit.access){
        load('notice/all', {
            cache: 1000 * 60 * 10
        }).then((result)=>{
            if(result.secuses){
                Storage.set('account_notice', result.notice.map(n=>n))

                call(result.notice)
            }
            else call([])
        }).catch(()=>{
            call([])
        })
    }
    else call([])
}

function subscribes(params = {}, secuses, error){
    if(Permit.access){
        load('notifications/all').then((result)=>{
            if(params.to_card_subscribe){
                let cards = []

                result.notifications.forEach(n => {
                    let card = Arrays.decodeJson(n.card, {})
                        card.subscribe = n

                        delete card.subscribe.card

                    cards.push(card)
                })

                secuses({
                    results: cards
                })
            }
            else{
                secuses({
                    results: result.notifications.map(r=> Arrays.decodeJson(r.card,{}))
                })
            }
        }).catch(error ? error : ()=>{})
    }
    else if(error) error({decode_code: 403})
}

function subscribeToTranslation(params = {}, call, error){
    if(Permit.access && params.voice){
        load('notifications/add', {}, {
            voice: params.voice,
            data: JSON.stringify(Utils.clearCard(params.card)),
            episode: params.episode,
            season: params.season
        }).then((result)=>{
            if(result.limited) Modal.limited()
            else if(call) call()
        }).catch(error ? error : ()=>{})
    }
    else if(error) error({decode_code: 403})
}

export default {
    url,
    load,
    user,
    persons,
    plugins,
    pluginToggle,
    subscribes,
    notices,
    subscribeToTranslation,
    clear: network.clear
}
