@component('mail::message')
# {{ trans('mail.hello').' '.$target.','}}

{{ trans('mail.bulk_checkout_introduction', ['count' => $count]) }}

@component('mail::table')
| {{ trans('mail.asset_tag') }} | {{ trans('general.asset_model') }} | {{ trans('mail.serial') }} | {{ trans('general.status') }} |
|:------------- |:------------- |:------------- |:------------- |
@foreach($assets as $asset)
| {{ $asset->asset_tag }} | {{ $asset->model->name ?? 'N/A' }} | {{ $asset->serial ?? 'N/A' }} | {{ $asset->assetstatus->name ?? 'N/A' }} |
@endforeach
@endcomponent

@if ($note)
**{{ trans('mail.additional_notes') }}**

{{ $note }}
@endif

@if ($admin)
**{{ trans('general.administrator') }}:** {{ $admin->display_name }}
@endif

{{ trans('mail.best_regards') }}

{{ $snipeSettings->site_name }}

@endcomponent
