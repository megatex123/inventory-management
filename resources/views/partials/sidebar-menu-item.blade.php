{{-- Recursive sidebar node renderer — one of: root link (Dashboard), group
     (top-level collapsible), header (h6 section label), or nested link. --}}
@if($item->type === 'link' && is_null($item->parent_id))
  <li class="nav-item active">
    <router-link class="nav-link" to="{{ $item->route }}">
      <i class="{{ $item->icon }}"></i>
      <span>{{ $item->label }}</span>
    </router-link>
  </li>
@elseif($item->type === 'group')
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#menu-{{ $item->id }}" aria-expanded="true" aria-controls="menu-{{ $item->id }}">
      <i class="{{ $item->icon }}"></i>
      <span>{{ $item->label }}</span>
    </a>
    <div id="menu-{{ $item->id }}" class="collapse" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        @foreach($item->children as $child)
          @include('partials.sidebar-menu-item', ['item' => $child])
        @endforeach
      </div>
    </div>
  </li>
@elseif($item->type === 'header')
  @if($item->divider_before)
    <hr class="sidebar-divider my-1">
  @endif
  <h6 class="collapse-header text-primary font-weight-bold">{!! $item->label !!}</h6>
  @foreach($item->children as $child)
    @include('partials.sidebar-menu-item', ['item' => $child])
  @endforeach
@elseif($item->type === 'link')
  <router-link class="collapse-item" to="{{ $item->route }}">{{ $item->label }}</router-link>
@endif
