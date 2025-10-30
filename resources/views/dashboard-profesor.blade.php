@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('general.dashboard') }}
    @parent
@stop

{{-- Page content --}}
@section('content')

@if ($snipeSettings->dashboard_message!='')
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <!-- /.box-header -->
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">
                        {!!  Helper::parseEscapedMarkedown($snipeSettings->dashboard_message)  !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <!-- panel -->

    <div class="col-lg-2 col-xs-6">
        <a href="{{ route('apartado.index') }}">
            <div class="dashboard small-box bg-blue">
                <div class="inner">
                    <h3 style="font-size: 20px; min-height: 44px; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Apartado de Equipo</h3>
                    <p>Transacciones</p>
                </div>
                <div class="icon" aria-hidden="true">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <span class="small-box-footer">
                    Ir <i class="fa fa-arrow-circle-right"></i>
                </span>
            </div>
        </a>
    </div>

    <div class="col-lg-2 col-xs-6">
        <a href="{{ route('hardware.index') }}">
            <!-- small hardware box -->
            <div class="dashboard small-box bg-teal">
                <div class="inner">
                    <h3>{{ number_format(\App\Models\Asset::AssetsForShow()->count()) }}</h3>
                    <p>{{ trans('general.assets') }}</p>
                </div>
                <div class="icon" aria-hidden="true">
                    <x-icon type="assets" />
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right" />
                </span>
            </div>
        </a>
    </div><!-- ./col -->

    <div class="col-lg-2 col-xs-6">
        <!-- small accessories box -->
        <a href="{{ route('accessories.index') }}">
            <div class="dashboard small-box bg-orange">
                <div class="inner">
                    <h3>{{ number_format($counts['accessory']) }}</h3>
                    <p>{{ trans('general.accessories') }}</p>
                </div>
                <div class="icon" aria-hidden="true">
                    <x-icon type="accessories" />
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right" />
                </span>
            </div>
        </a>
    </div><!-- ./col -->

    <div class="col-lg-2 col-xs-6">
        <!-- small components box -->
        <a href="{{ route('components.index') }}">
            <div class="dashboard small-box bg-yellow">
                <div class="inner">
                    <h3>{{ number_format($counts['component']) }}</h3>
                    <p>{{ trans('general.components') }}</p>
                </div>
                <div class="icon" aria-hidden="true">
                    <x-icon type="components" />
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right" />
                </span>
            </div>
        </a>
    </div><!-- ./col -->
</div>

@if ($counts['grand_total'] == 0)
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.dashboard_info') }}</h2>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="progress">
                                <div class="progress-bar progress-bar-yellow" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%">
                                    <span class="sr-only">{{ trans('general.60_percent_warning') }}</span>
                                </div>
                            </div>

                            <p><strong>{{ trans('general.dashboard_empty') }}</strong></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            @can('create', \App\Models\Asset::class)
                                <a class="btn bg-teal" style="width: 100%" href="{{ route('hardware.create') }}">{{ trans('general.new_asset') }}</a>
                            @endcan
                        </div>
                        <div class="col-md-2">
                            @can('create', \App\Models\License::class)
                                <a class="btn bg-maroon" style="width: 100%" href="{{ route('licenses.create') }}">{{ trans('general.new_license') }}</a>
                            @endcan
                        </div>
                        <div class="col-md-2">
                            @can('create', \App\Models\Accessory::class)
                                <a class="btn bg-orange" style="width: 100%" href="{{ route('accessories.create') }}">{{ trans('general.new_accessory') }}</a>
                            @endcan
                        </div>
                        <div class="col-md-2">
                            @can('create', \App\Models\Consumable::class)
                                <a class="btn bg-purple" style="width: 100%" href="{{ route('consumables.create') }}">{{ trans('general.new_consumable') }}</a>
                            @endcan
                        </div>
                        <div class="col-md-2">
                            @can('create', \App\Models\Component::class)
                                <a class="btn bg-yellow" style="width: 100%" href="{{ route('components.create') }}">{{ trans('general.new_component') }}</a>
                            @endcan
                        </div>
                        <div class="col-md-2">
                            @can('create', \App\Models\User::class)
                                <a class="btn bg-light-blue" style="width: 100%" href="{{ route('users.create') }}">{{ trans('general.new_user') }}</a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="row">
        <div class="col-md-6">
            <!-- Locations -->
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.locations') }}</h2>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <x-icon type="minus" />
                            <span class="sr-only">{{ trans('general.collapse') }}</span>
                        </button>
                    </div>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table
                                    data-cookie-id-table="dashLocationSummary"
                                    data-height="400"
                                    data-side-pagination="server"
                                    data-pagination="false"
                                    data-sort-order="desc"
                                    data-sort-field="assets_count"
                                    id="dashLocationSummary"
                                    class="table table-striped snipe-table"
                                    data-url="{{ route('api.locations.index', ['sort' => 'assets_count', 'order' => 'asc']) }}">
                                    <thead>
                                        <tr>
                                            <th class="col-sm-3" data-visible="true" data-field="name" data-formatter="locationsLinkFormatter" data-sortable="true">{{ trans('general.name') }}</th>
                                            <th class="col-sm-1" data-visible="true" data-field="assets_count" data-sortable="true">
                                                <x-icon type="assets" />
                                                <span class="sr-only">{{ trans('general.asset_count') }}</span>
                                            </th>
                                            <th class="col-sm-1" data-visible="true" data-field="assigned_assets_count" data-sortable="true">
                                                {{ trans('general.assigned') }}
                                            </th>
                                            <th class="col-sm-1" data-visible="true" data-field="users_count" data-sortable="true">
                                                <x-icon type="users" />
                                                <span class="sr-only">{{ trans('general.people') }}</span>
                                            </th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div> <!-- /.col -->
                        <div class="text-center col-md-12" style="padding-top: 10px;">
                            <a href="{{ route('locations.index') }}" class="btn btn-primary btn-sm" style="width: 100%">{{ trans('general.viewall') }}</a>
                        </div>
                    </div> <!-- /.row -->
                </div><!-- /.box-body -->
            </div> <!-- /.box -->
        </div>

        <div class="col-md-6">
            <!-- Categories -->
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.asset') }} {{ trans('general.categories') }}</h2>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <x-icon type="minus" />
                            <span class="sr-only">{{ trans('general.collapse') }}</span>
                        </button>
                    </div>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table
                                    data-cookie-id-table="dashCategorySummary"
                                    data-height="400"
                                    data-pagination="false"
                                    data-side-pagination="server"
                                    data-sort-order="desc"
                                    data-sort-field="assets_count"
                                    id="dashCategorySummary"
                                    class="table table-striped snipe-table"
                                    data-url="{{ route('api.categories.index', ['sort' => 'assets_count', 'order' => 'asc']) }}">
                                    <thead>
                                        <tr>
                                            <th class="col-sm-3" data-visible="true" data-field="name" data-formatter="categoriesLinkFormatter" data-sortable="true">{{ trans('general.name') }}</th>
                                            <th class="col-sm-3" data-visible="true" data-field="category_type" data-sortable="true">
                                                {{ trans('general.type') }}
                                            </th>
                                            <th class="col-sm-1" data-visible="true" data-field="assets_count" data-sortable="true">
                                                <x-icon type="assets" />
                                                <span class="sr-only">{{ trans('general.asset_count') }}</span>
                                            </th>
                                            <th class="col-sm-1" data-visible="true" data-field="accessories_count" data-sortable="true">
                                                <x-icon type="licenses" />
                                                <span class="sr-only">{{ trans('general.accessories_count') }}</span>
                                            </th>
                                            <th class="col-sm-1" data-visible="true" data-field="consumables_count" data-sortable="true">
                                                <x-icon type="consumables" />
                                                <span class="sr-only">{{ trans('general.consumables_count') }}</span>
                                            </th>
                                            <th class="col-sm-1" data-visible="true" data-field="components_count" data-sortable="true">
                                                <x-icon type="components" />
                                                <span class="sr-only">{{ trans('general.components_count') }}</span>
                                            </th>
                                            <th class="col-sm-1" data-visible="true" data-field="licenses_count" data-sortable="true">
                                                <x-icon type="licenses" />
                                                <span class="sr-only">{{ trans('general.licenses_count') }}</span>
                                            </th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div> <!-- /.col -->
                        <div class="text-center col-md-12" style="padding-top: 10px;">
                            <a href="{{ route('categories.index') }}" class="btn btn-primary btn-sm" style="width: 100%">{{ trans('general.viewall') }}</a>
                        </div>
                    </div> <!-- /.row -->
                </div><!-- /.box-body -->
            </div> <!-- /.box -->
        </div>
    </div>
@endif

@stop

@section('moar_scripts')
@include ('partials.bootstrap-table', ['simple_view' => true, 'nopages' => true])
@stop