<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
    <style>
        .navigation-form { display: grid; gap: 20px; }
        .navigation-section { background: #fafafa; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; }
        .navigation-section h4 { color: #17a2b8; font-size: 18px; margin: 0 0 8px; padding-bottom: 8px; border-bottom: 2px solid #17a2b8; }
        .navigation-help { color: #6b7280; font-size: 12px; line-height: 1.5; margin: 5px 0 16px; }
        .navigation-grid { display: grid; gap: 14px; grid-template-columns: minmax(0, 2fr) minmax(150px, 1.4fr) 90px 100px; }
        .navigation-grid.header { color: #374151; font-size: 12px; font-weight: 700; margin-bottom: 6px; text-transform: uppercase; }
        .navigation-field label { color: #333; display: block; font-weight: 700; margin-bottom: 6px; }
        .navigation-field input[type="text"], .navigation-field input[type="url"], .navigation-field input[type="number"], .navigation-field textarea, .navigation-field select { border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; min-height: 40px; padding: 9px; width: 100%; }
        .navigation-toggle { align-items: center; display: flex; gap: 8px; min-height: 40px; }
        .navigation-toggle input { height: 18px; width: 18px; }
        .navigation-column { background: #fff; border: 1px solid #dbe1e8; border-radius: 8px; margin-top: 16px; padding: 16px; }
        .navigation-column-head { display: grid; gap: 14px; grid-template-columns: minmax(0, 2fr) 90px 100px; }
        .navigation-links { border-top: 1px solid #e5e7eb; margin-top: 14px; padding-top: 14px; }
        .navigation-copy-grid { display: grid; gap: 16px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .navigation-social-grid { display: grid; gap: 16px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .navigation-save { background: #28a745; border: 0; border-radius: 4px; color: #fff; cursor: pointer; font-size: 16px; font-weight: 700; padding: 15px 25px; width: 100%; }
        @media (max-width: 900px) { .navigation-grid, .navigation-column-head { grid-template-columns: 1fr 1fr; } .navigation-grid.header { display: none; } }
        @media (max-width: 600px) { .navigation-grid, .navigation-column-head, .navigation-copy-grid, .navigation-social-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="wrapper">
        @include('admin.partials.sidebar')
        <div class="main-content">
            @include('admin.partials.header')
            <div class="content">
                <div class="card">
                    <h3>Header & Footer Settings</h3>
                    <p class="navigation-help">Manage shared public navigation without changing authentication actions. Destinations are restricted to existing public routes; lower order values render first.</p>
                    @include('admin.partials.validation-errors')
                    @if(session('success'))
                        <div style="color: green; margin-bottom: 15px; font-weight: bold;">{{ session('success') }}</div>
                    @endif

                    @php
                        $headerLinks = old('header_navigation', $configuration['header_navigation']);
                        $footerColumns = old('footer_columns', $configuration['footer_columns']);
                    @endphp

                    <form action="{{ route('admin.frontend_navigation.update') }}" method="POST" class="navigation-form">
                        @csrf

                        <section class="navigation-section">
                            <h4>Header navigation</h4>
                            <p class="navigation-help">These links render from the same data in desktop and mobile menus. Sign in and registration remain available independently.</p>
                            <div class="navigation-grid header" aria-hidden="true"><span>Label</span><span>Destination</span><span>Order</span><span>Visible</span></div>
                            @foreach($headerLinks as $index => $link)
                                <div class="navigation-grid">
                                    <div class="navigation-field"><label for="header-label-{{ $index }}">Label</label><input id="header-label-{{ $index }}" type="text" name="header_navigation[{{ $index }}][label]" value="{{ $link['label'] ?? '' }}" required maxlength="80"></div>
                                    <div class="navigation-field"><label for="header-route-{{ $index }}">Destination</label><select id="header-route-{{ $index }}" name="header_navigation[{{ $index }}][route]" required>@foreach($destinationOptions as $routeName => $routeLabel)<option value="{{ $routeName }}" @selected(($link['route'] ?? '') === $routeName)>{{ $routeLabel }}</option>@endforeach</select></div>
                                    <div class="navigation-field"><label for="header-order-{{ $index }}">Order</label><input id="header-order-{{ $index }}" type="number" name="header_navigation[{{ $index }}][order]" value="{{ $link['order'] ?? 0 }}" min="0" max="1000" required></div>
                                    <div class="navigation-field"><label for="header-visible-{{ $index }}">Visible</label><div class="navigation-toggle"><input type="hidden" name="header_navigation[{{ $index }}][is_visible]" value="0"><input id="header-visible-{{ $index }}" type="checkbox" name="header_navigation[{{ $index }}][is_visible]" value="1" @checked((bool) ($link['is_visible'] ?? false))><span>Show</span></div></div>
                                </div>
                            @endforeach
                        </section>

                        <section class="navigation-section">
                            <h4>Footer content</h4>
                            <div class="navigation-copy-grid">
                                <div class="navigation-field"><label for="footer_description">Brand description</label><textarea id="footer_description" name="footer_description" rows="4" maxlength="1000">{{ old('footer_description', $setting->footer_description) }}</textarea></div>
                                <div class="navigation-field"><label for="footer_copyright">Copyright suffix</label><textarea id="footer_copyright" name="footer_copyright" rows="4" maxlength="500">{{ old('footer_copyright', $setting->footer_copyright) }}</textarea><p class="navigation-help">The current year and site name are added automatically.</p></div>
                            </div>

                            @foreach($footerColumns as $columnIndex => $column)
                                <div class="navigation-column">
                                    <div class="navigation-column-head">
                                        <div class="navigation-field"><label for="column-title-{{ $columnIndex }}">Column title</label><input id="column-title-{{ $columnIndex }}" type="text" name="footer_columns[{{ $columnIndex }}][title]" value="{{ $column['title'] ?? '' }}" required maxlength="80"></div>
                                        <div class="navigation-field"><label for="column-order-{{ $columnIndex }}">Order</label><input id="column-order-{{ $columnIndex }}" type="number" name="footer_columns[{{ $columnIndex }}][order]" value="{{ $column['order'] ?? 0 }}" min="0" max="1000" required></div>
                                        <div class="navigation-field"><label for="column-visible-{{ $columnIndex }}">Visible</label><div class="navigation-toggle"><input type="hidden" name="footer_columns[{{ $columnIndex }}][is_visible]" value="0"><input id="column-visible-{{ $columnIndex }}" type="checkbox" name="footer_columns[{{ $columnIndex }}][is_visible]" value="1" @checked((bool) ($column['is_visible'] ?? false))><span>Show</span></div></div>
                                    </div>
                                    <div class="navigation-links">
                                        <div class="navigation-grid header" aria-hidden="true"><span>Link label</span><span>Destination</span><span>Order</span><span>Visible</span></div>
                                        @foreach(($column['links'] ?? []) as $linkIndex => $link)
                                            <div class="navigation-grid">
                                                <div class="navigation-field"><label for="footer-label-{{ $columnIndex }}-{{ $linkIndex }}">Link label</label><input id="footer-label-{{ $columnIndex }}-{{ $linkIndex }}" type="text" name="footer_columns[{{ $columnIndex }}][links][{{ $linkIndex }}][label]" value="{{ $link['label'] ?? '' }}" required maxlength="80"></div>
                                                <div class="navigation-field"><label for="footer-route-{{ $columnIndex }}-{{ $linkIndex }}">Destination</label><select id="footer-route-{{ $columnIndex }}-{{ $linkIndex }}" name="footer_columns[{{ $columnIndex }}][links][{{ $linkIndex }}][route]" required>@foreach($destinationOptions as $routeName => $routeLabel)<option value="{{ $routeName }}" @selected(($link['route'] ?? '') === $routeName)>{{ $routeLabel }}</option>@endforeach</select></div>
                                                <div class="navigation-field"><label for="footer-order-{{ $columnIndex }}-{{ $linkIndex }}">Order</label><input id="footer-order-{{ $columnIndex }}-{{ $linkIndex }}" type="number" name="footer_columns[{{ $columnIndex }}][links][{{ $linkIndex }}][order]" value="{{ $link['order'] ?? 0 }}" min="0" max="1000" required></div>
                                                <div class="navigation-field"><label for="footer-visible-{{ $columnIndex }}-{{ $linkIndex }}">Visible</label><div class="navigation-toggle"><input type="hidden" name="footer_columns[{{ $columnIndex }}][links][{{ $linkIndex }}][is_visible]" value="0"><input id="footer-visible-{{ $columnIndex }}-{{ $linkIndex }}" type="checkbox" name="footer_columns[{{ $columnIndex }}][links][{{ $linkIndex }}][is_visible]" value="1" @checked((bool) ($link['is_visible'] ?? false))><span>Show</span></div></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </section>

                        <section class="navigation-section">
                            <h4>Social links</h4>
                            <p class="navigation-help">Only complete HTTP or HTTPS URLs are accepted. Empty social links are not rendered publicly.</p>
                            <div class="navigation-social-grid">
                                @foreach(['facebook_url' => 'Facebook', 'x_url' => 'X / Twitter', 'linkedin_url' => 'LinkedIn', 'github_url' => 'GitHub', 'youtube_url' => 'YouTube'] as $field => $label)
                                    <div class="navigation-field"><label for="{{ $field }}">{{ $label }} URL</label><input id="{{ $field }}" type="url" name="{{ $field }}" value="{{ old($field, $setting->{$field}) }}" maxlength="2048" placeholder="https://"></div>
                                @endforeach
                            </div>
                        </section>

                        <button type="submit" class="navigation-save">Save Header & Footer Settings</button>
                    </form>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>
</body>
</html>
