<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title></title>
    <style>
        .b-box{
            width:26%;
            height:150px;
            float:left;
            margin-left:4%;
            margin-top:20px;

        }
        .s-box{
            width:100%;
            text-align:center;
            font-size:15px;
            height:30px;
            line-height:15px;
        }
        .s-img{
            width:100%;
            height:80px;
        }
    </style>
</head>
<body style="">

<div style="padding:10px;width:100%;" >
    @foreach($number_info as $key => $vo)
        <div class="b-box">
            <img class="s-img" src="http://{{ $url }}/barcodegen/example/code/test_code128.php?text={{ $vo -> package_num }}" />
            <div class="s-box" >{{ substr($vo -> package_num,0,4).' '.substr($vo -> package_num,4,3).' '.substr($vo -> package_num,7,3).' '.substr($vo -> package_num,10) }}</div>
        </div>
    @endforeach
</div>



</body>
</html>
