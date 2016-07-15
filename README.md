# amtools

# Cachebust (from v1.4.0)
**Samples**
```
<link rel="stylesheet" href="{{ gruntCacheBust('grunt-cache-bust.json', 'resources/css/all.css') }}">

<script src="{{ gruntCacheBust('grunt-cache-bust.json', 'resources/scripts/all.min.js') }}"></script>
```

# Image filters (from v1.3.0)
**Possible effects**:
- blur
- colorize
- gray (black & white)
- negative
- sharpen

**Samples**:

```
<img src="{{ craft.amTools.getImageUrl(entry.image.first(), { filters: 'gray', width: 150, height: 150 }) }}" alt="">

<img src="{{ entry.image.first()|image_url({ filters: 'gray', width: 150, height: 150, mode: 'fit' }) }}" alt="">

<img src="{{ entry.image.first()|image_url({ filters: { effect: 'colorize', color: '#FF00D0' }, width: 150, height: 150 }) }}" alt="">

<img src="{{ entry.image.first()|image_url({ filters: ['gray', 'sharpen'], width: 150, height: 150 }) }}" alt="">

<img src="{{ entry.image.first()|image_url({ filters: ['negative', 'blur', { effect: 'colorize', color: '#FF00D0' }], width: 150, height: 150 }) }}" alt="">
```
