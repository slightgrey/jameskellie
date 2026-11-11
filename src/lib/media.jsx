/**
 * <img> from the image data built by JK\Boot::image().
 * Extra props (loading, fetchpriority, decoding…) are passed through.
 */
export function Img({ img, className, ...rest }) {
  if (!img || !img.src) return null;
  return (
    <img
      src={img.src}
      srcSet={img.srcset || undefined}
      sizes={img.srcset ? img.sizes || undefined : undefined}
      width={img.width || undefined}
      height={img.height || undefined}
      alt={img.alt ?? ''}
      className={className}
      {...rest}
    />
  );
}
