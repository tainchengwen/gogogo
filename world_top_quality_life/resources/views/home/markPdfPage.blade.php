<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title></title>
    <style>
        html,body{
            height:200px;width:320px;
        }
        .b-box{
            margin-top:5%;
            height:90%;
            width:80%;
            float:left;
            margin-left:18%;
        }
        .top-box{
            height:10%;
            line-height:18px;
            font-size:20px;
            width:100%;
            text-align: center;
        }
        .bottom-box{
            height:79%;
            margin-top:1%;
            width:100%;
        }
        .s-img{
            width:100%;
            height:100%;
        }
        .bb-box{
            width:100%;
            height:10%;
            line-height:20px;
            font-size:20px;
            text-align: center;
        }
    </style>
</head>
<body>

<div style="height:100%;width:100%;" >
    <div class="b-box" style="padding-top:10px;">
        <div class="top-box">
            识别码：{{ $sp_number }}
        </div>
        <div class="bottom-box">
            <img class="s-img" src="http://{{ $url }}/barcodegen/example/code/test_code128.php?text={{ $sp_number }}"  />
        </div>
        <div class="bb-box">
            仓位：E34
        </div>
    </div>
</div>



</body>
</html>
