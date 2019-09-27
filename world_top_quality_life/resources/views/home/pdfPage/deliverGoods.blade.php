<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title></title>
    <style type="text/css">
        body {
            margin: 0;
        }
        .content {
            width: 750px;
            
            margin: 0 auto;
            padding:0 10px;
            background-color: #ffffff;
        }
        .table{
            height:100%;
            width:100%;
            border:1px solid #000000;

        }
        .table td{
            border:1px solid #000000;
        }
        .s-img{
            width:100%;
            height:40px;
        }

        .logo{
            width:70px;
            height:70px;
            float:left;
            margin-left:186px;
        }
        .fa_info{
            line-height: 21px;
            margin:0;
        }
    </style>
</head>
<body>
<div class="content">
    <div  style="height:532px;border-bottom:1px dashed #000000;overflow: hidden">
        <div style="height:100px;width:100%;margin:0 auto;text-align: center;position:relative;">
            <div style="line-height: 70px;height:70px;margin-top:5px;">
                <img src="{{ asset('img/logo.png') }}" class="logo" />
                <h1 style="margin:0;line-height: 50px;float: left;padding-top:10px;font-size:28px;">展鹏国际供应链有限公司</h1>
                <div style="clear:both;"></div>
            </div>


            <span style="position:absolute;bottom:-30px;right:12px;">
                <a>送貨日期：</a><a style="margin-left:45px;">年</a>
                <a style="margin-left:20px;">月</a>
                <a style="margin-left:20px;">日</a>
            </span>

        </div>

        <div style="height:84px;width:100%;border-top:2px solid #000000;border-bottom:2px solid #000000;text-align:left;">
            <h5 style="line-height: 28px;margin:0;">收件人：余先生</h5>
            <h5 style="line-height: 28px;margin:0;">Tel：97629625</h5>
            <h5 style="line-height: 28px;margin:0;">地址：香港新界屯門區新安街16號百勝工業大廈5樓B&D</h5>
        </div>
        <div style="height:84px;width:50%;text-align:left;float:left;">
            <h5 class="fa_info">发件人：{{ $name }}</h5>
            <h5 class="fa_info">入倉號：{{ $canghao }}</h5>
            <h5 class="fa_info">Tel：{{ $tel }}</h5>
            <h5 class="fa_info">身份證號：{{ $card }}</h5>
        </div>
        <div style="height:84px;width:50%;float:right;position:relative;">
            <div style="width:350px;height:50px;position:absolute;top:20px;">
                <img class="s-img" src="http://{{ env('PDF_URL') }}/barcodegen/example/code/test_code128.php?text={{ $canghao }}"  />
                <h5 style="line-height:10px;margin:0;text-align: center;">{{ $canghao }}</h5>
            </div>
        </div>
        <div style="clear: both;"></div>



        <div style="height:100px;width:100%;">
            <table class="table" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="width:100px;">運輸方式：</td>
                    <td>{{ $sub_type }}</td>
                    <td style="width:200px;">司机姓名：</td>
                    <td style="width:200px;">司机电话：</td>
                </tr>
                <tr>
                    <td>物品件數：</td>
                    <td>{{ $num }}</td>
                    <td>重量：</td>
                    <td>{{ $weight }}</td>
                </tr>
                <tr>
                    <td>貨值：</td>
                    <td></td>
                    <td>幣種：</td>
                    <td></td>
                </tr>
            </table>
        </div>
        <div style="width:100%;height:142px;position:relative;">
            <h4 style="line-height: 28px;margin:0;font-weight: 400;">備註：</h4>
            <h5 style="line-height: 28px;margin:0;font-weight: 400;">收件人只核對貨品外包裝件數，不核對內件明細。</h5>
            <h5 style="line-height: 28px;margin:0;font-weight: 400;">發件人在選擇國籍物流運輸方式後必須填寫國際物流單號</h5>
            <h5 style="line-height: 28px;margin:0;font-weight: 400;">如需要提供點數服務，必須提供明細清單，且需送收貨方當面清點</h5>
            <div style="width:320px;height:80px;line-height:40px;border:2px solid #000000;position:absolute;right:0;top:37px;">
                <h5 style="margin:0;font-weight: 400;">簽收人：</h5>
                <h5 style="margin:0;font-weight: 400;">簽收时间：</h5>
            </div>

        </div>
    </div>

    <div style="height:532px;overflow: hidden">
        <div style="height:100px;width:100%;margin:0 auto;text-align: center;position:relative;">
            <div style="line-height: 70px;height:70px;margin-top:5px;">
                <img src="{{ asset('img/logo.png') }}" class="logo" />
                <h1 style="margin:0;line-height: 50px;float: left;padding-top:10px;font-size:28px;">展鹏国际供应链有限公司</h1>
                <div style="clear:both;"></div>
            </div>


            <span style="position:absolute;bottom:-30px;right:12px;">
                <a>送貨日期：</a><a style="margin-left:45px;">年</a>
                <a style="margin-left:20px;">月</a>
                <a style="margin-left:20px;">日</a>
            </span>

        </div>

        <div style="height:84px;width:100%;border-top:2px solid #000000;border-bottom:2px solid #000000;text-align:left;">
            <h5 style="line-height: 28px;margin:0;">收件人：余先生</h5>
            <h5 style="line-height: 28px;margin:0;">Tel：97629625</h5>
            <h5 style="line-height: 28px;margin:0;">地址：香港新界屯門區新安街16號百勝工業大廈5樓B&D</h5>
        </div>
        <div style="height:84px;width:50%;text-align:left;float:left;">
            <h5 class="fa_info">发件人：{{ $name }}</h5>
            <h5 class="fa_info">入倉號：{{ $canghao }}</h5>
            <h5 class="fa_info">Tel：{{ $tel }}</h5>
            <h5 class="fa_info">身份證號：{{ $card }}</h5>
        </div>
        <div style="height:84px;width:50%;float:right;position:relative;">
            <div style="width:350px;height:50px;position:absolute;top:20px;">
                <img class="s-img" src="http://{{ env('PDF_URL') }}/barcodegen/example/code/test_code128.php?text={{ $canghao }}"  />
                <h5 style="line-height:10px;margin:0;text-align: center;">{{ $canghao }}</h5>
            </div>
        </div>
        <div style="clear: both;"></div>



        <div style="height:100px;width:100%;">
            <table class="table" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="width:100px;">運輸方式：</td>
                    <td>{{ $sub_type }}</td>
                    <td style="width:200px;">司机姓名：</td>
                    <td style="width:200px;">司机电话：</td>
                </tr>
                <tr>
                    <td>物品件數：</td>
                    <td>{{ $num }}</td>
                    <td>重量：</td>
                    <td>{{ $weight }}</td>
                </tr>
                <tr>
                    <td>貨值：</td>
                    <td></td>
                    <td>幣種：</td>
                    <td></td>
                </tr>
            </table>
        </div>
        <div style="width:100%;height:142px;position:relative;">
            <h4 style="line-height: 28px;margin:0;font-weight: 400;">備註：</h4>
            <h5 style="line-height: 28px;margin:0;font-weight: 400;">收件人只核對貨品外包裝件數，不核對內件明細。</h5>
            <h5 style="line-height: 28px;margin:0;font-weight: 400;">發件人在選擇國籍物流運輸方式後必須填寫國際物流單號</h5>
            <h5 style="line-height: 28px;margin:0;font-weight: 400;">如需要提供點數服務，必須提供明細清單，且需送收貨方當面清點</h5>
            <div style="width:320px;height:80px;line-height:40px;border:2px solid #000000;position:absolute;right:0;top:37px;">
                <h5 style="margin:0;font-weight: 400;">簽收人：</h5>
                <h5 style="margin:0;font-weight: 400;">簽收时间：</h5>
            </div>

        </div>
    </div>

</div>
</body>
</html>
