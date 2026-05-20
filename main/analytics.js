(function(){
    'use strict';

    // Base path to actions folder. Set window.ANALYTICS_BASE on pages when needed.
    var BASE = window.ANALYTICS_BASE || '../actions/';

    function post(endpoint, data) {
        try {
            return fetch(BASE + endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: Object.keys(data).map(function(k){
                    return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
                }).join('&')
            }).then(function(res){
                return res.json().catch(function(){ return {}; });
            }).catch(function(err){
                console && console.debug && console.debug('Analytics POST error', err);
            });
        } catch(e) {
            console && console.debug && console.debug('Analytics exception', e);
        }
    }

    function getPageInfo() {
        var metaName = document.querySelector('meta[name="page-name"]');
        var metaType = document.querySelector('meta[name="page-type"]');
        var page = (metaName && metaName.content) || document.title || location.pathname;
        var type = (metaType && metaType.content) || (location.pathname.indexOf('/search') !== -1 ? 'destination' : 'home');
        return { page: page, type: type };
    }

    // Record page view on load
    document.addEventListener('DOMContentLoaded', function(){
        var info = getPageInfo();
        post('record_view.php', { page_name: info.page, page_type: info.type });
    });

    // Delegate clicks: record clicks for links and elements with data-analytics attribute
    document.addEventListener('click', function(e){
        try {
            var target = e.target;
            // climb up to find clickable element
            while (target && target !== document) {
                if (target.matches && (target.matches('a') || target.matches('button') || target.hasAttribute('data-analytics'))) break;
                target = target.parentNode;
            }
            if (!target || target === document) return;

            var info = getPageInfo();
            var label = (target.getAttribute && (target.getAttribute('data-analytics') || target.getAttribute('href') || target.textContent)) || '';
            post('record_click.php', { page_name: info.page + ' - ' + label, page_type: info.type });
        } catch(err) {
            // swallow errors
        }
    }, true);

})();
