<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
    <style>
        .section-box { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 5px; background: #fafafa; }
        .section-title { font-weight: bold; margin-bottom: 15px; border-bottom: 2px solid #17a2b8; padding-bottom: 5px; display: inline-block; }
        .input-group { margin-bottom: 10px; display: flex; gap: 10px; }
        .input-group input, .input-group textarea { flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-add { background: #28a745; color: white; padding: 5px 10px; border: none; cursor: pointer; font-size: 12px; margin-bottom: 10px; }
        .btn-remove { background: #dc3545; color: white; padding: 8px 12px; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="wrapper">
        @include('admin.partials.sidebar')
        <div class="main-content">
            @include('admin.partials.header')
            <div class="content">
                <div class="card">
                    <h3>About Page Management</h3>
                    @if(session('success'))
                        <div style="color: green; margin-bottom: 15px; font-weight: bold;">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('admin.about.update') }}" method="POST">
                        @csrf

                        <!-- Hero Section -->
                        <div class="section-box">
                            <div class="section-title">Hero Section</div>
                            <p>Badge: <input type="text" name="hero_badge" value="{{ $about->hero_badge }}" style="width:100%;"></p>
                            <p>Title: <input type="text" name="hero_title" value="{{ $about->hero_title }}" style="width:100%;"></p>
                            <p>Description: <textarea name="hero_description" style="width:100%;" rows="3">{{ $about->hero_description }}</textarea></p>
                        </div>

                        <!-- Trust Stats Section -->
                        <div class="section-box">
                            <div class="section-title">Trust Stats (e.g. 100%, 4.8x)</div>
                            <div id="stats-container">
                                @foreach($about->stats as $index =>$stat)
                                <div class="input-group">
                                    <input type="text" name="stats[{{$index}}][value]" value="{{ $stat->value }}" placeholder="Value (e.g. 100%)">
                                    <input type="text" name="stats[{{$index}}][label]" value="{{ $stat->label }}" placeholder="Label (e.g. Zero-Touch Autopilot)">
                                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn-add" onclick="addStat()">+ Add Stat</button>
                        </div>

                        <!-- Comparison Section -->
                        <div class="section-box">
                            <div class="section-title">Comparison Table</div>
                            <p>Title: <input type="text" name="comparison_title" value="{{ $about->comparison_title }}" style="width:100%;"></p>
                            <p>Description: <textarea name="comparison_description" style="width:100%;" rows="2">{{ $about->comparison_description }}</textarea></p>
                            
                            <div id="comp-container">
                                @foreach($about->comparisons as $index =>$comp)
                                <div class="input-group">
                                    <input type="text" name="comparisons[{{$index}}][capability]" value="{{ $comp->capability }}" placeholder="Capability">
                                    <input type="text" name="comparisons[{{$index}}][traditional_text]" value="{{ $comp->traditional_text }}" placeholder="Traditional Text">
                                    <input type="text" name="comparisons[{{$index}}][bte_text]" value="{{ $comp->bte_text }}" placeholder="BTE Text">
                                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn-add" onclick="addComp()">+ Add Comparison Row</button>
                        </div>

                        <!-- Architecture Section -->
                        <div class="section-box">
                            <div class="section-title">Architecture Steps</div>
                            <div id="arch-container">
                                @foreach($about->architectures as $index =>$arch)
                                <div class="input-group">
                                    <input type="number" name="architectures[{{$index}}][step_number]" value="{{ $arch->step_number }}" placeholder="Step (1,2,3)" style="flex:0.2;">
                                    <input type="text" name="architectures[{{$index}}][title]" value="{{ $arch->title }}" placeholder="Title">
                                    <input type="text" name="architectures[{{$index}}][description]" value="{{ $arch->description }}" placeholder="Description">
                                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn-add" onclick="addArch()">+ Add Step</button>
                        </div>

                        <button type="submit" style="padding: 10px 20px; background: #17a2b8; color: white; border: none; font-size: 16px; cursor:pointer;">Update About Page Data</button>
                    </form>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>

    <!-- JavaScript for adding dynamic rows -->
    <script>
        let statCount = {{ count($about->stats) }};
        function addStat() {
            let html = `<div class="input-group"><input type="text" name="stats[${statCount}][value]" placeholder="Value"><input type="text" name="stats[${statCount}][label]" placeholder="Label"><button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button></div>`;
            document.getElementById('stats-container').insertAdjacentHTML('beforeend', html);
            statCount++;
        }

        let compCount = {{ count($about->comparisons) }};
        function addComp() {
            let html = `<div class="input-group"><input type="text" name="comparisons[${compCount}][capability]" placeholder="Capability"><input type="text" name="comparisons[${compCount}][traditional_text]" placeholder="Traditional Text"><input type="text" name="comparisons[${compCount}][bte_text]" placeholder="BTE Text"><button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button></div>`;
            document.getElementById('comp-container').insertAdjacentHTML('beforeend', html);
            compCount++;
        }

        let archCount = {{ count($about->architectures) }};
        function addArch() {
            let html = `<div class="input-group"><input type="number" name="architectures[${archCount}][step_number]" placeholder="Step" style="flex:0.2;"><input type="text" name="architectures[${archCount}][title]" placeholder="Title"><input type="text" name="architectures[${archCount}][description]" placeholder="Description"><button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button></div>`;
            document.getElementById('arch-container').insertAdjacentHTML('beforeend', html);
            archCount++;
        }
    </script>
</body>
</html>