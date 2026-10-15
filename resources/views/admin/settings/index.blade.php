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
                    <h3>Site Settings</h3>
                    
                    @if(session('success'))
                        <div style="color: green; margin-bottom: 15px;">{{ session('success') }}</div>
                    @endif

                    <!-- enctype="multipart/form-data" oboshhoi dite hobe image upload er jonno -->
                    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 300px;">
                                <p>Site Name: <br><input type="text" name="site_name" value="{{ $setting->site_name ?? '' }}" style="width: 100%; padding: 8px;"></p>
                                <p>Email: <br><input type="email" name="email" value="{{ $setting->email ?? '' }}" style="width: 100%; padding: 8px;"></p>
                                <p>Mobile: <br><input type="text" name="mobile" value="{{ $setting->mobile ?? '' }}" style="width: 100%; padding: 8px;"></p>
                                <p>Facebook URL: <br><input type="text" name="facebook_url" value="{{ $setting->facebook_url ?? '' }}" style="width: 100%; padding: 8px;"></p>
                                <p>Address: <br><textarea name="address" rows="3" style="width: 100%; padding: 8px;">{{ $setting->address ?? '' }}</textarea></p>
                            </div>
                            
                            <div style="flex: 1; min-width: 300px;">
                                <p>Logo (Dark): <br>
                                    <input type="file" name="logo_dark"><br>
                                    @if(isset($setting->logo_dark))
                                        <img src="{{ asset('storage/'.$setting->logo_dark) }}" height="50" style="margin-top: 10px; background: #eee; padding: 5px;">
                                    @endif
                                </p>
                                <p>Logo (Light): <br>
                                    <input type="file" name="logo_light"><br>
                                    @if(isset($setting->logo_light))
                                        <img src="{{ asset('storage/'.$setting->logo_light) }}" height="50" style="margin-top: 10px; background: #333; padding: 5px;">
                                    @endif
                                </p>
                                <p>Favicon: <br>
                                    <input type="file" name="favicon"><br>
                                    @if(isset($setting->favicon))
                                        <img src="{{ asset('storage/'.$setting->favicon) }}" height="32" style="margin-top: 10px;">
                                    @endif
                                </p>
                            </div>
                        </div>

                        <button type="submit" style="padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; margin-top: 15px;">Save Settings</button>
                    </form>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>
</body>
</html>