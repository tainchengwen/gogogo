<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title></title>
    <style>
        html,body{

        }
        .b-box{
            margin-top:5%;
            height:80%;
            width:90%;
            float:left;
            margin-left:5%;
        }
        .s-box{
            width:100%;
            text-align:center;
            font-size:16px;
            line-height:18px;
        }
        .s-img{
            width:100%;
            height:90%;
        }
    </style>
</head>
<body>


<div style="height:80px;width:100%;" >
    <div class="b-box" style="padding-top:10px;">

        <img class="s-img" src="http://{{ $url }}/barcodegen/example/code/test_code128.php?text={{ $sp_number }}"  />
        <div class="s-box" >{{ $show_number }}</div>
        <div class="s-box" >{{ $ext2 }}@if($ext)<a style="margin-left:10px;">{{ $ext }}</a>@endif</div>
    </div>
</div>



</body>
</html>
