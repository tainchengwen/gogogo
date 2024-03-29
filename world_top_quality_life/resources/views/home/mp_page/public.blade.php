<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
<meta name="_token" content="{{ csrf_token() }}"/>
<link  rel="stylesheet" href="{{ asset('etk/css/index_etk.css') }}"/>
<script>
    (function(doc, win) {
        var docEl = doc.documentElement,
            isIOS = navigator.userAgent.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/),
            dpr = isIOS ? Math.min(win.devicePixelRatio, 3) : 1,
            dpr = window.top === window.self ? dpr : 1, //被iframe引用时，禁止缩放
            dpr = 1,
            scale = 1 / dpr,
            resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize';
        docEl.dataset.dpr = dpr;
        var metaEl = doc.createElement('meta');
        metaEl.name = 'viewport';
        metaEl.content = 'initial-scale=' + scale + ',maximum-scale=' + scale + ', minimum-scale=' + scale;
        docEl.firstElementChild.appendChild(metaEl);
        var recalc = function() {
            var width = docEl.clientWidth;
            if (width / dpr > 750) {
                width = 100 * dpr;
            }
            // 乘以100，px : rem = 100 : 1
            docEl.style.fontSize = 75 * (width / 750) + 'px';
        };
        recalc()
        if (!doc.addEventListener) return;
        win.addEventListener(resizeEvt, recalc, false);
    })(document, window);
</script>