<!DOCTYPE html>
<html lang="en">
<head>@include('admin.partials.head') @include('admin.pricing.partials.form-styles')</head>
<body><div class="wrapper">@include('admin.partials.sidebar')<div class="main-content">@include('admin.partials.header')<div class="content"><div class="card"><h3>Edit Pricing Plan</h3>@include('admin.partials.validation-errors')<form action="{{ route('admin.pricing.update', $plan) }}" method="POST" enctype="multipart/form-data">@csrf @include('admin.pricing.partials.form-fields', ['submitLabel' => 'Update Pricing Plan'])</form></div></div>@include('admin.partials.footer')</div></div></body>
</html>
