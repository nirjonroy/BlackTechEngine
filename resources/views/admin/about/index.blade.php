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
    @php
        $stats = array_values(old('stats', $about->stats->map->only(['id', 'value', 'label'])->all()));
        $comparisons = array_values(old('comparisons', $about->comparisons->map->only(['id', 'capability', 'traditional_text', 'bte_text'])->all()));
        $architectures = array_values(old('architectures', $about->architectures->map->only(['id', 'step_number', 'title', 'description'])->all()));
        $coreValues = array_values(old('core_values', $about->coreValues->map->only(['id', 'icon', 'title', 'description'])->all()));
    @endphp

    <div class="wrapper">
        @include('admin.partials.sidebar')
        <div class="main-content">
            @include('admin.partials.header')
            <div class="content">
                <div class="card">
                    <h3>About Page Management</h3>
                    @include('admin.partials.validation-errors')
                    @if(session('success'))
                        <div style="color: green; margin-bottom: 15px; font-weight: bold;">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div style="color: #dc3545; margin-bottom: 15px; font-weight: bold;">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('admin.about.update') }}" method="POST">
                        @csrf

                        <div class="section-box">
                            <div class="section-title">Hero Section</div>
                            <p>Badge: <input type="text" name="hero_badge" value="{{ old('hero_badge', $about->hero_badge) }}" style="width:100%;"></p>
                            <p>Title: <input type="text" name="hero_title" value="{{ old('hero_title', $about->hero_title) }}" style="width:100%;"></p>
                            <p>Description: <textarea name="hero_description" style="width:100%;" rows="3">{{ old('hero_description', $about->hero_description) }}</textarea></p>
                        </div>

                        <div class="section-box">
                            <div class="section-title">Trust Stats (e.g. 100%, 4.8x)</div>
                            <input type="hidden" name="stats_submitted" value="1">
                            <div id="stats-container">
                                @foreach($stats as $index => $stat)
                                    <div class="input-group">
                                        @if(! empty($stat['id']))
                                            <input type="hidden" name="stats[{{ $index }}][id]" value="{{ $stat['id'] }}">
                                        @endif
                                        <input type="text" name="stats[{{ $index }}][value]" value="{{ $stat['value'] ?? '' }}" placeholder="Value (e.g. 100%)">
                                        <input type="text" name="stats[{{ $index }}][label]" value="{{ $stat['label'] ?? '' }}" placeholder="Label (e.g. Zero-Touch Autopilot)">
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn-add" onclick="addStat()">+ Add Stat</button>
                        </div>

                        <div class="section-box">
                            <div class="section-title">Comparison Table</div>
                            <p>Title: <input type="text" name="comparison_title" value="{{ old('comparison_title', $about->comparison_title) }}" style="width:100%;"></p>
                            <p>Description: <textarea name="comparison_description" style="width:100%;" rows="2">{{ old('comparison_description', $about->comparison_description) }}</textarea></p>
                            <input type="hidden" name="comparisons_submitted" value="1">
                            <div id="comp-container">
                                @foreach($comparisons as $index => $comparison)
                                    <div class="input-group">
                                        @if(! empty($comparison['id']))
                                            <input type="hidden" name="comparisons[{{ $index }}][id]" value="{{ $comparison['id'] }}">
                                        @endif
                                        <input type="text" name="comparisons[{{ $index }}][capability]" value="{{ $comparison['capability'] ?? '' }}" placeholder="Capability">
                                        <input type="text" name="comparisons[{{ $index }}][traditional_text]" value="{{ $comparison['traditional_text'] ?? '' }}" placeholder="Traditional Text">
                                        <input type="text" name="comparisons[{{ $index }}][bte_text]" value="{{ $comparison['bte_text'] ?? '' }}" placeholder="BTE Text">
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn-add" onclick="addComp()">+ Add Comparison Row</button>
                        </div>

                        <div class="section-box">
                            <div class="section-title">Architecture Steps</div>
                            <p>Badge: <input type="text" name="architecture_badge" value="{{ old('architecture_badge', $about->architecture_badge) }}" style="width:100%;"></p>
                            <p>Title: <input type="text" name="architecture_title" value="{{ old('architecture_title', $about->architecture_title) }}" style="width:100%;"></p>
                            <p>Description: <textarea name="architecture_description" style="width:100%;" rows="2">{{ old('architecture_description', $about->architecture_description) }}</textarea></p>
                            <input type="hidden" name="architectures_submitted" value="1">
                            <div id="arch-container">
                                @foreach($architectures as $index => $architecture)
                                    <div class="input-group">
                                        @if(! empty($architecture['id']))
                                            <input type="hidden" name="architectures[{{ $index }}][id]" value="{{ $architecture['id'] }}">
                                        @endif
                                        <input type="number" name="architectures[{{ $index }}][step_number]" value="{{ $architecture['step_number'] ?? '' }}" placeholder="Step (1,2,3)" style="flex:0.2;">
                                        <input type="text" name="architectures[{{ $index }}][title]" value="{{ $architecture['title'] ?? '' }}" placeholder="Title">
                                        <input type="text" name="architectures[{{ $index }}][description]" value="{{ $architecture['description'] ?? '' }}" placeholder="Description">
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn-add" onclick="addArch()">+ Add Step</button>
                        </div>

                        <div class="section-box">
                            <div class="section-title">Core Values</div>
                            <p>Title: <input type="text" name="core_values_title" value="{{ old('core_values_title', $about->core_values_title) }}" style="width:100%;"></p>
                            <p>Description: <textarea name="core_values_description" style="width:100%;" rows="2">{{ old('core_values_description', $about->core_values_description) }}</textarea></p>
                            <input type="hidden" name="core_values_submitted" value="1">
                            <div id="core-values-container">
                                @foreach($coreValues as $index => $coreValue)
                                    <div class="input-group">
                                        @if(! empty($coreValue['id']))
                                            <input type="hidden" name="core_values[{{ $index }}][id]" value="{{ $coreValue['id'] }}">
                                        @endif
                                        <input type="text" name="core_values[{{ $index }}][icon]" value="{{ $coreValue['icon'] ?? '' }}" placeholder="Icon (e.g. shield-check)">
                                        <input type="text" name="core_values[{{ $index }}][title]" value="{{ $coreValue['title'] ?? '' }}" placeholder="Title">
                                        <textarea name="core_values[{{ $index }}][description]" placeholder="Description" rows="2">{{ $coreValue['description'] ?? '' }}</textarea>
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn-add" onclick="addCoreValue()">+ Add Core Value</button>
                        </div>

                        <div class="section-box">
                            <div class="section-title">Call to Action</div>
                            <p>Title: <input type="text" name="cta_title" value="{{ old('cta_title', $about->cta_title) }}" style="width:100%;"></p>
                            <p>Description: <textarea name="cta_description" style="width:100%;" rows="3">{{ old('cta_description', $about->cta_description) }}</textarea></p>
                            <p>Primary Button Text: <input type="text" name="cta_btn1_text" value="{{ old('cta_btn1_text', $about->cta_btn1_text) }}" style="width:100%;"></p>
                            <p>Primary Button URL: <input type="url" name="cta_btn1_link" value="{{ old('cta_btn1_link', $about->cta_btn1_link) }}" style="width:100%;"></p>
                            <p>Secondary Button Text: <input type="text" name="cta_btn2_text" value="{{ old('cta_btn2_text', $about->cta_btn2_text) }}" style="width:100%;"></p>
                            <p>Secondary Button URL: <input type="url" name="cta_btn2_link" value="{{ old('cta_btn2_link', $about->cta_btn2_link) }}" style="width:100%;"></p>
                        </div>

                        <button type="submit" style="padding: 10px 20px; background: #17a2b8; color: white; border: none; font-size: 16px; cursor:pointer;">Update About Page Data</button>
                    </form>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>

    <script>
        let statCount = {{ count($stats) }};
        function addStat() {
            const html = `<div class="input-group"><input type="text" name="stats[${statCount}][value]" placeholder="Value"><input type="text" name="stats[${statCount}][label]" placeholder="Label"><button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button></div>`;
            document.getElementById('stats-container').insertAdjacentHTML('beforeend', html);
            statCount++;
        }

        let compCount = {{ count($comparisons) }};
        function addComp() {
            const html = `<div class="input-group"><input type="text" name="comparisons[${compCount}][capability]" placeholder="Capability"><input type="text" name="comparisons[${compCount}][traditional_text]" placeholder="Traditional Text"><input type="text" name="comparisons[${compCount}][bte_text]" placeholder="BTE Text"><button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button></div>`;
            document.getElementById('comp-container').insertAdjacentHTML('beforeend', html);
            compCount++;
        }

        let archCount = {{ count($architectures) }};
        function addArch() {
            const html = `<div class="input-group"><input type="number" name="architectures[${archCount}][step_number]" placeholder="Step" style="flex:0.2;"><input type="text" name="architectures[${archCount}][title]" placeholder="Title"><input type="text" name="architectures[${archCount}][description]" placeholder="Description"><button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button></div>`;
            document.getElementById('arch-container').insertAdjacentHTML('beforeend', html);
            archCount++;
        }

        let coreValueCount = {{ count($coreValues) }};
        function addCoreValue() {
            const html = `<div class="input-group"><input type="text" name="core_values[${coreValueCount}][icon]" placeholder="Icon"><input type="text" name="core_values[${coreValueCount}][title]" placeholder="Title"><textarea name="core_values[${coreValueCount}][description]" placeholder="Description" rows="2"></textarea><button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button></div>`;
            document.getElementById('core-values-container').insertAdjacentHTML('beforeend', html);
            coreValueCount++;
        }
    </script>
</body>
</html>
