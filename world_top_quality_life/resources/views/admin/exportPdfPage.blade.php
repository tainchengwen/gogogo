<style>
    .table td input{
        float:left;
    }
</style>
<input type="hidden" name="_token" value="{{ csrf_token() }}" />
<section class="content" style="background-color: #ffffff">
    <div class="row">
        <div class="col-md-12">
            <form>
            <table class="table" id="myTable">
                <thead>
                <tr>
                    <th>文件下载</th>
                </tr>
                </thead>

                <tbody>
                @foreach($compact['package_ids_res'] as $key => $vo)
                <tr>
                    <td><a href="{{ admin_url('exportPdf').'/'.$vo }}" target="_blank">PDF-{{ $key + 1 }}</a></td>
                </tr>
                @endforeach
                </tbody>
            </table>
            </form>
        </div>
    </div>



</section>