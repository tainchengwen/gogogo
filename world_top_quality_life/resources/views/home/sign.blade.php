<!DOCTYPE html>
<html>
<head>
    <title>用户协议</title>
    @include('layouts.home_header')
</head>
<body>
<div class="content">
    <br/>

    <br/>

    <h5 style="text-align:center;margin:0 auto;">欢迎您的关注</h5>
    <div class="btn_big_bg" style="position:fixed;left:0;bottom:5px;width:100%;margin-bottom: 0.2rem;" onclick="location.href='{{ url('/agreeSign') }}'">
        <button class="btn_big" onclick="location.href='{{ url('/agreeSign') }}'" >同意</button>
    </div>
    <!--
    <input type="button" style="width:100px;" value="同意" onclick="location.href='{{ url('/agreeSign') }}'" />
        -->

</div>


</body>
</html>
