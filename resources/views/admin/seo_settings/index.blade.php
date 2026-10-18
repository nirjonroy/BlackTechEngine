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
                    <h3>SEO Settings List <a href="{{ route('admin.seo.create') }}" style="float:right; font-size:14px; background:#17a2b8; color:white; padding:5px 10px; text-decoration:none; border-radius:3px;">+ Add New</a></h3>
                    
                    @if(session('success'))
                        <div style="color: green; margin-bottom: 15px; font-weight: bold;">{{ session('success') }}</div>
                    @endif

                    <table border="1" width="100%" cellpadding="10" style="border-collapse: collapse; margin-top:15px;">
                        <tr style="background:#f4f6f9;">
                            <th>Page Name</th>
                            <th>SEO Title</th>
                            <th>Meta Image</th>
                            <th>Action</th>
                        </tr>
                        @foreach($seos as $seo)
                        <tr>
                            <td>{{ $seo->page_name }}</td>
                            <td>{{ $seo->seo_title }}</td>
                            <td>
                                @if($seo->meta_image)
                                    <img src="{{ asset('storage/'.$seo->meta_image) }}" height="40" alt="img">
                                @else
                                    No Image
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.seo.edit', $seo->id) }}" style="color:#28a745; margin-right:10px;">Edit</a>
                                <form action="{{ route('admin.seo.destroy', $seo->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this SEO setting?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="color:#dc3545; background:none; border:none; padding:0; cursor:pointer;">Delete</button>
                                </form>
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
