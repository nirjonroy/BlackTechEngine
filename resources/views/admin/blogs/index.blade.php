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
                    <h3>Blogs List <a href="{{ route('admin.blogs.create') }}" style="float:right; font-size:14px; background:#17a2b8; color:white; padding:5px 10px; text-decoration:none; border-radius:3px;">+ Add New Blog</a></h3>
                    @if(session('success')) <div style="color: green; margin-bottom: 15px;">{{ session('success') }}</div> @endif
                    <table border="1" width="100%" cellpadding="10" style="border-collapse: collapse; margin-top:15px;">
                        <tr style="background:#f4f6f9;">
                            <th>Image</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        @foreach($blogs as $blog)
                        <tr>
                            <td>@if($blog->image) <img src="{{ asset('storage/'.$blog->image) }}" height="40"> @endif</td>
                            <td>{{ $blog->title }}</td>
                            <td>{{ $blog->category }}</td>
                            <td>
                                @if(!$blog->published_at)
                                    Draft
                                @elseif($blog->published_at->isFuture())
                                    Scheduled for {{ $blog->published_at->format('M j, Y g:i A') }}
                                @else
                                    Published {{ $blog->published_at->format('M j, Y g:i A') }}
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.blogs.edit', $blog->id) }}" style="color:#28a745; margin-right:10px;">Edit</a>
                                <form action="{{ route('admin.blogs.destroy', $blog->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this blog?')">
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
