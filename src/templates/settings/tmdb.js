let html = `<div>
    <div class="settings-param selector" data-type="select" data-name="tmdb_lang">
        <div class="settings-param__name">TMDB</div>
        <div class="settings-param__value"></div>
        <div class="settings-param__descr">#{settings_rest_tmdb_lang}</div>
    </div>

    <div class="settings-param selector" data-type="select" data-name="poster_size">
        <div class="settings-param__name">#{settings_rest_tmdb_posters}</div>
        <div class="settings-param__value"></div>
    </div>

    <div class="settings-param selector" data-type="toggle" data-name="proxy_tmdb_auto">
        <div class="settings-param__name">#{settings_rest_tmdb_prox_auto}</div>
        <div class="settings-param__value"></div>
    </div>

    <div class="settings-param selector" data-type="toggle" data-name="proxy_tmdb">
        <div class="settings-param__name">#{settings_rest_tmdb_prox}</div>
        <div class="settings-param__value"></div>
    </div>

</div>`

export default html
