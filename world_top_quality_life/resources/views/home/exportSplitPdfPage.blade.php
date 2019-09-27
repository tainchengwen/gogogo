<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title></title>
    <style>
        html,body{
            height:100px;width:160px;
        }
        .b-box{

            height:100px;
            float:left;
            margin-left:5%;
        }
        .s-box{
            width:100%;
            text-align:center;
            font-size:15px;
            height:15px;
            line-height:15px;
        }
        .s-img{
            width:100%;
            height:40px;
        }
    </style>
</head>
<body>

<div style="height:100%;width:100%;" >
    <div class="b-box" style="padding-top:10px;">

        <img class="s-img" src="http://{{ $url }}/barcodegen/example/code/test_code128.php?text={{ $sp_number }}"  />
        <div class="s-box" style="font-size:10px;">{{ $show_number }}</div>
    </div>
</div>



</body>
</html>
