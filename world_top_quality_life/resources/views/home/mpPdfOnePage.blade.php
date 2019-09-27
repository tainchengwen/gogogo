<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title></title>
    <style>
        html,body{
            @if($type ==1)height:1100px;@endif
        }
        .b-box{
            margin-top:5%;
            height:90%;
            width:80%;
            float:left;
            margin-left:10%;
        }
        .s-box{
            width:100%;
            text-align:center;
            font-size:22px;
            height:10%;
            line-height:25px;
        }
        .s-img{
            width:100%;
            height:70px;
        }
    </style>
</head>
<body>
@foreach($number_info as $vo)
    <div style="width:100%;height:80px;" >
        <div class="b-box" style="padding-top:10px;">
            <img class="s-img" src="http://{{ $url }}/barcodegen/example/code/test_code128.php?text={{ $vo -> package_num }}"  />
            <div class="s-box" >{{ substr($vo -> package_num,0,4).' '.substr($vo -> package_num,4,3).' '.substr($vo -> package_num,7,3).' '.substr($vo -> package_num,10) }}</div>
        </div>
    </div>
@endforeach


</body>
</html>
