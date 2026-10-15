<!DOCTYPE html>
<html lang="en">
<head>@include('admin.partials.head')</head>
<body>
    <div class="wrapper">
        @include('admin.partials.sidebar')
        <div class="main-content">
            @include('admin.partials.header')
            <div class="content">
                <div class="card">
                    <h3>Pricing Plans <a href="{{ route('admin.pricing.create') }}" style="float:right; font-size:14px; background:#17a2b8; color:white; padding:5px 10px; text-decoration:none; border-radius:3px;">+ Add New Plan</a></h3>
                    @if(session('success')) <div style="color:green; margin-bottom:15px;">{{ session('success') }}</div> @endif
                    <table border="1" width="100%" cellpadding="10" style="border-collapse:collapse; margin-top:15px;">
                        <tr style="background:#f4f6f9;">
                            <th>Plan Name</th>
                            <th>Monthly / Annual</th>
                            <th>Popular</th>
                            <th>Action</th>
                        </tr>
                        @foreach($plans as $plan)
                        <tr>
                            <td>{{ $plan->name }} <br><small>{{ $plan->title }}</small></td>
                            <td>${{ $plan->monthly_price }} / ${{ $plan->annual_price }}</td>
                            <td>{{ $plan->is_popular ? 'Yes' : 'No' }}</td>
                            <td>
                                <a href="{{ route('admin.pricing.edit', $plan->id) }}" style="color:#28a745; margin-right:10px;">Edit</a>
                                <a href="{{ route('admin.pricing.destroy', $plan->id) }}" style="color:#dc3545;" onclick="return confirm('Delete?')">Delete</a>
                            </td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>
</body>
</html>