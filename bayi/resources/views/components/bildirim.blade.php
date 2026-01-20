


<div class="dropdown d-inline-block">
    <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-notifications-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="width:70px">
        <i class="mdi mdi-bell-outline"></i>
        <span class="badge badge-danger badge-pill">{{$sayi}}</span>
    </button>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0" aria-labelledby="page-header-notifications-dropdown">
        <div class="p-3">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="m-0"> Bildirimler </h6>
                </div>
                <div class="col-auto">
                    <a href="#!" class="small"> Tümü</a>
                </div>
            </div>
        </div>
        <div data-simplebar style="max-height: 230px;">
            @foreach($bildirim as $show)

                <a href="{{$show->url}}" class="text-reset notification-item noticestat" id="{{$show->id}}">
                    <div class="media">
                        <div class="avatar-xs mr-3">
                                                <span class="avatar-title bg-primary rounded-circle font-size-16">
                                                    <i class="bx bx-cart"></i>
                                                </span>
                        </div>
                        <div class="media-body">
                            <h6 class="mt-0 mb-1">{{$show->bildirim}}</h6>
                            <div class="font-size-12 text-muted">
                                <p class="mb-1"><u>{{$show->name}}</u> {{$show->mesaj}}</p>
                                <p class="mb-0"><i class="mdi mdi-clock-outline"></i> {{dateformat($show->tarih)}}</p>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach




        </div>
        <div class="p-2 border-top">
            <a class="btn btn-sm btn-link font-size-14 btn-block text-center" onclick="allnotice()" href="javascript:void(0)">
                <i class="mdi mdi-arrow-right-circle mr-1" ></i> Tümü..
            </a>
        </div>

    </div>
</div>

