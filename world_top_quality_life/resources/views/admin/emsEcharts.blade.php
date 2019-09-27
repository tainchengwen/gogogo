<script src="{{asset('js/jquery-1.11.1.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('js/echarts.min.js') }}"></script>



<div class="containss" style="width: 100%;height:500px;" id="containss"></div>

<script type="text/javascript">
    $(function(){
        var height = $(window).height();
        $('#containss').css('height',(height - 100 )+'px');

    })

    var xNumber = [];
    var yNumber = [];
    var maxNumber;
    var ems_status = [];
    var ems_status_str = [];
    function getData()
    {
        $.post("{{ admin_url('emsEchartsAjax') }}", {
            "_token": "{{ csrf_token() }}"
        }, function(data) {
            console.log(data);
            xNumber = data.data_arr;
            yNumber = data.status_arr;
            maxNumber = data.max;
            ems_status = data.ems_status;
            ems_status_str = data.ems_status_str;
            chart();
        });
    }
    getData();

    // 实现画图的功能
    function chart() {
        var myChart = echarts.init(document.getElementById("containss"));
        var option;

        option = {
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'cross',
                    crossStyle: {
                        color: '#999'
                    }
                }
            },
            toolbox: {
                feature: {
                    dataView: {show: true, readOnly: false},
                    magicType: {show: true, type: ['line', 'bar']},
                    restore: {show: true},
                    saveAsImage: {show: true}
                }
            },

            legend: {
                data:ems_status_str,
            },
            xAxis: [
                {
                    type: 'category',
                    data: xNumber,
                    axisPointer: {
                        type: 'shadow'
                    }
                }
            ],
            yAxis: [
                {
                    type: 'value',
                    name: '个数',
                    min: 0,
                    max: maxNumber,
                    interval: 10,
                    axisLabel: {
                        formatter: '{value} 个'
                    }
                }
            ],
            series: [
                {
                    name:ems_status[1],
                    type:'bar',
                    data:yNumber[1]
                },
                {
                    name:ems_status[2],
                    type:'bar',
                    data:yNumber[2]
                },
                {
                    name:ems_status[3],
                    type:'bar',
                    data:yNumber[3]
                },
                {
                    name:ems_status[4],
                    type:'bar',
                    data:yNumber[4]
                },
                {
                    name:ems_status[5],
                    type:'bar',
                    data:yNumber[5]
                },
                {
                    name:ems_status[6],
                    type:'bar',
                    data:yNumber[6]
                },
                {
                    name:ems_status[7],
                    type:'bar',
                    data:yNumber[7]
                },
                {
                    name:ems_status[8],
                    type:'bar',
                    data:yNumber[8]
                },
                {
                    name:ems_status[9],
                    type:'bar',
                    data:yNumber[9]
                }
            ]
        };

        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(option);
    }

</script>