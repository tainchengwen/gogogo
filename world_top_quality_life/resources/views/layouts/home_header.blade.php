<meta charset="UTF-8">
<link  rel="stylesheet" href="{{ asset('css/index.css') }}"/>
<script src="{{asset('js/jquery-1.11.1.min.js')}}"></script>
<script src="{{asset('js/layer/2.2/layer.js')}}"></script>
<meta name="_token" content="{{ csrf_token() }}"/>
<meta name="viewport" content="user-scalable=0">
<style>
/*
    .layui-layer-msg{
        height: 3.75rem;
        width: 20rem;
        font-size: 1.87rem!important;
        line-height: 3.75rem;
    }
    .layui-layer-dialog .layui-layer-content{
        font-size: 1.87rem;
    }
    */
</style>
<script>
    @if(session('message'))
        $(function(){
            layer.msg('{{session('message')}}',{icon: 5});
        })
    @endif
</script>
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