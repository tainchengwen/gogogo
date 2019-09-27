<div style="width:150px;overflow-x: scroll">
    <a href="{{ $url_address }}" style="font-size:20px;" ><i class="fa fa-bank"></i></a>
    <a href="{{ $url_chai }}" style="margin-left:10px;font-size:20px;" ><i class="fa fa-bomb"></i></a>
    <a href="{{ $url_edit }}" style="margin-left:10px;font-size:20px;" ><i class="fa fa-edit"></i></a>
    @if($sub_type == 2 || $sub_type == 3)
    <a href="{{ $url_pdf }}" style="margin-left:10px;font-size:20px;" target="_blank" ><i class="fa fa-print"></i></a>
    @endif
    @if($sub_type > 0 )
    <a href="{{ $url_eye }}" style="margin-left:10px;font-size:20px;" target="_blank" ><i class="fa fa-eye"></i></a>
    @endif
</div>