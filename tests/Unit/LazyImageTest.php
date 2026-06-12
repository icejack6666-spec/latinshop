<?php

declare(strict_types=1);

namespace LatinShop\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * LazyImageTest
 *
 * Tests unitarios para el helper LazyImage (Paso 10).
 * No requiere base de datos ni sesión.
 */
final class LazyImageTest extends TestCase
{
    // ─── img() básico ────────────────────────────────────────────────────────

    public function testImgGeneratesDataSrcByDefault(): void
    {
        $html = \LazyImage::img('foto.jpg', 'Mi foto');

        $this->assertStringContainsString('data-src="foto.jpg"', $html);
        $this->assertStringContainsString('class="ls-lazy"', $html);
        $this->assertStringContainsString('alt="Mi foto"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
    }

    public function testImgEagerModeUsesRealSrc(): void
    {
        $html = \LazyImage::img('hero.jpg', 'Hero', ['eager' => true]);

        $this->assertStringContainsString('src="hero.jpg"', $html);
        $this->assertStringNotContainsString('data-src', $html);
        $this->assertStringNotContainsString('ls-lazy', $html);
        $this->assertStringContainsString('loading="eager"', $html);
    }

    public function testImgIncludesDecoding(): void
    {
        $html = \LazyImage::img('img.jpg', '');
        $this->assertStringContainsString('decoding="async"', $html);
    }

    public function testImgAppendsCustomClass(): void
    {
        $html = \LazyImage::img('img.jpg', '', ['class' => 'cuenta-card__img']);
        $this->assertStringContainsString('ls-lazy cuenta-card__img', $html);
    }

    public function testImgIncludesDimensionsWhenProvided(): void
    {
        $html = \LazyImage::img('img.jpg', '', ['width' => 400, 'height' => 300]);
        $this->assertStringContainsString('width="400"', $html);
        $this->assertStringContainsString('height="300"', $html);
    }

    public function testImgIncludesFallback(): void
    {
        $html = \LazyImage::img('img.jpg', '', ['fallback' => 'placeholder.webp']);
        $this->assertStringContainsString('data-fallback="placeholder.webp"', $html);
    }

    public function testImgEscapesXssInSrc(): void
    {
        $html = \LazyImage::img('"><script>alert(1)</script>', 'alt');
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testImgEscapesXssInAlt(): void
    {
        $html = \LazyImage::img('img.jpg', '"><script>xss</script>');
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testImgIncludesSrcset(): void
    {
        $html = \LazyImage::img('img.jpg', '', ['srcset' => 'img-2x.jpg 2x']);
        $this->assertStringContainsString('data-srcset="img-2x.jpg 2x"', $html);
    }

    public function testImgSrcsetEagerUsesRealAttribute(): void
    {
        $html = \LazyImage::img('img.jpg', '', [
            'eager'  => true,
            'srcset' => 'img-2x.jpg 2x',
        ]);
        $this->assertStringContainsString('srcset="img-2x.jpg 2x"', $html);
        $this->assertStringNotContainsString('data-srcset', $html);
    }

    // ─── avatar() ────────────────────────────────────────────────────────────

    public function testAvatarWithUrlGeneratesImg(): void
    {
        $html = \LazyImage::avatar('https://example.com/avatar.jpg', 'usuario', 'post-avatar');
        $this->assertStringContainsString('data-src="https://example.com/avatar.jpg"', $html);
        $this->assertStringContainsString('post-avatar', $html);
    }

    public function testAvatarWithNullUrlGeneratesSvgInitials(): void
    {
        $html = \LazyImage::avatar(null, 'JuanPerez', 'post-avatar');
        // Sin URL real → SVG con iniciales inline
        $this->assertStringContainsString('data:image/svg+xml,', $html);
        $this->assertStringContainsString('J', rawurldecode($html));
    }

    public function testAvatarWithEmptyUsernameGeneratesPlaceholder(): void
    {
        $html = \LazyImage::avatar(null, '', 'avatar');
        $this->assertStringContainsString('data:image/svg+xml,', $html);
        // El SVG de placeholder tiene '?'
        $this->assertStringContainsString('?', rawurldecode($html));
    }

    public function testAvatarEagerMode(): void
    {
        $html = \LazyImage::avatar('avatar.jpg', 'user', 'cls', 38, true);
        $this->assertStringContainsString('loading="eager"', $html);
        $this->assertStringNotContainsString('ls-lazy', $html);
    }

    public function testAvatarIncludesDimensions(): void
    {
        $html = \LazyImage::avatar('avatar.jpg', 'user', 'cls', 64);
        $this->assertStringContainsString('width="64"', $html);
        $this->assertStringContainsString('height="64"', $html);
    }

    // ─── bg() ────────────────────────────────────────────────────────────────

    public function testBgGeneratesDiv(): void
    {
        $html = \LazyImage::bg('fondo.jpg', 'hero-section', '<h1>Hola</h1>');
        $this->assertStringContainsString('<div', $html);
        $this->assertStringContainsString('data-bg="fondo.jpg"', $html);
        $this->assertStringContainsString('ls-lazy-bg', $html);
        $this->assertStringContainsString('hero-section', $html);
        $this->assertStringContainsString('<h1>Hola</h1>', $html);
    }

    public function testBgEscapesUrl(): void
    {
        $html = \LazyImage::bg('"><script>xss</script>', 'cls');
        $this->assertStringNotContainsString('<script>', $html);
    }

    // ─── picture() ───────────────────────────────────────────────────────────

    public function testPictureGeneratesPictureTag(): void
    {
        $html = \LazyImage::picture(
            [['src' => 'img.webp', 'type' => 'image/webp']],
            'img.jpg',
            'Alt text'
        );
        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('</picture>', $html);
        $this->assertStringContainsString('<source', $html);
        $this->assertStringContainsString('data-srcset="img.webp"', $html);
        $this->assertStringContainsString('<img', $html);
    }

    public function testPictureEagerUsesRealAttributes(): void
    {
        $html = \LazyImage::picture(
            [['src' => 'img.webp', 'type' => 'image/webp']],
            'img.jpg',
            'Alt',
            ['eager' => true]
        );
        $this->assertStringContainsString('srcset="img.webp"', $html);
        $this->assertStringNotContainsString('data-srcset', $html);
    }
}
