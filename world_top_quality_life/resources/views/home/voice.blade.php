<script src="{{ asset('js/voice.js') }}"></script>
<style>
    #bigMsgError{
        width:100%;
        height:400px;
        font-size:80px;
        font-weight: bolder;
        line-height:200px;
        position:fixed;
        top:20%;
        left:0;
        text-align: center;
        background:#ffffff;
        border:5px solid #000000;
        color:orangered;
        z-index: 999;
        display:none;
    }
    #bigMsgSuccess{
        width:100%;
        height:400px;
        font-size:80px;
        font-weight: bolder;
        line-height:200px;
        position:fixed;
        top:20%;
        left:0;
        text-align: center;
        background:#ffffff;
        border:5px solid #000000;
        color:greenyellow;
        z-index: 999;
        display:none;
    }
</style>
<script>

    function noFind(){
        var player = $("#noFind")[0]; /*jquery对象转换成js对象*/
        player.play();
    }


    //找到了
    function findSuccess(){

        var player = $("#findSuccess")[0]; /*jquery对象转换成js对象*/
        player.play();
    }

    //请扫描商品编码
    function playerGoods(){
        var player = $("#playerGoods")[0]; /*jquery对象转换成js对象*/
        player.play();
    }

    //请扫描包裹编码
    function playerPakcage(){
        var player = $("#playerPakcage")[0]; /*jquery对象转换成js对象*/
        player.play();
    }

    //扫描成功
    function playMusic() {
        var player = $("#player")[0]; /*jquery对象转换成js对象*/
        player.play();
    }
</script>
<script>
    function bigMsg(msg,type){
        if(type == 'success'){
            $('#bigMsgSuccess').text(msg);
            layer.open({
                type: 1,
                title: false,
                closeBtn: 0,
                area: '516px',
                skin: 'layui-layer-nobg', //没有背景色
                shadeClose: true,
                content: $('#bigMsgSuccess')
            });
        }else{
            $('#bigMsgError').text(msg);
            layer.open({
                type: 1,
                title: false,
                closeBtn: 0,
                area: '516px',
                skin: 'layui-layer-nobg', //没有背景色
                shadeClose: true,
                content: $('#bigMsgError')
            });
        }



    }


</script>

<!-- 没找到 -->
<audio id="noFind" controls="controls" style="display:none;">
    <source src="{{ asset('wav/nofind.wav') }}"/>
</audio>

<!-- 找到了 -->
<audio id="findSuccess" controls="controls" style="display:none;">
    <source src="{{ asset('wav/findsuccess.wav') }}"/>
</audio>

<!- 请扫描商品编码-->
<audio id="playerGoods" controls="controls" style="display:none;">
    <source src="{{ asset('wav/playerGoods.wav') }}"/>
</audio>

<!-- 请扫描包裹编码 -->
<audio id="playerPakcage" controls="controls" style="display:none;">
    <source src="{{ asset('wav/playerPackage.wav') }}"/>
</audio>
<!--扫描成功-->
<audio id="player" controls="controls" style="display:none;">
    <source src="{{ asset('wav/success.wav') }}"/>
</audio>
<div id="bigMsgError" onclick="location.reload()">
    <div style="width:100%;height:100%;position:relative;">
        <a class="btn btn-sm btn-success"  style="font-size:25px;margin-top:5px;position:absolute;bottom:5px;right:5px;" onclick="location.reload()"  ><i class="fa fa-crosshairs"></i>刷新</a>
    </div>
</div>
<div id="bigMsgSuccess" onclick="location.reload()">
    <div style="width:100%;height:100%;position:relative;">
        <a class="btn btn-sm btn-success"  style="font-size:25px;margin-top:5px;position:absolute;bottom:5px;right:5px;" onclick="location.reload()"   ><i class="fa fa-crosshairs"></i>刷新</a>
    </div>
</div>

