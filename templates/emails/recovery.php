<?php
/**
 * Recovery email template (HTML).
 *
 * The bold move: the call-to-action is "the light we left on for you", a warm
 * lantern panel. A soft amber halo (radial-gradient where supported, solid warm
 * panel as the bulletproof fallback) sits behind the button, as if the shop left
 * a porch light burning so the shopper can find their way back to a cart that is
 * still being kept warm. Restraint everywhere else: a calm neutral letter, one
 * lit window. Accent is ember/lantern amber, never transactional blue.
 *
 * Email-safe: table layout, inline styles, bulletproof background fallbacks.
 * The accent is exposed via inline values rather than CSS custom properties
 * because most mail clients strip <style> and :root tokens.
 *
 * Available variables (all prefixed with recover_):
 *
 * @var string $recover_heading
 * @var string $recover_body
 * @var string $recover_button
 * @var string $recover_restore_url
 * @var string $recover_site_name
 * @var array<int, array{name:string, quantity:int, image:string, line_total:string}> $recover_items
 * @var string $recover_total Price HTML from wc_price(), empty to hide the total.
 * @var array{woo:bool, page:string, card:string, text:string, heading:string, soft:string, muted:string, line:string, accent:string, button_text:string, logo:string} $recover_palette
 *      Colours: the warm defaults, or the shop's WooCommerce email colours.
 *
 * @package Recover
 */

defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr(get_bloginfo('language')); ?>">
<head>
    <meta charset="<?php echo esc_attr(get_bloginfo('charset')); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light dark" />
    <meta name="supported-color-schemes" content="light dark" />
    <title><?php echo esc_html($recover_heading); ?></title>
</head>
<body style="margin:0;padding:0;background:<?php echo esc_attr($recover_palette['page']); ?>;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:<?php echo esc_attr($recover_palette['text']); ?>;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:<?php echo esc_attr($recover_palette['page']); ?>;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:<?php echo esc_attr($recover_palette['card']); ?>;border-radius:14px;overflow:hidden;border:1px solid <?php echo esc_attr($recover_palette['line']); ?>;">
                    <tr>
                        <td style="padding:36px 36px 8px;">
                            <?php if ($recover_palette['logo'] !== '') : ?>
                            <p style="margin:0 0 24px;"><img src="<?php echo esc_url($recover_palette['logo']); ?>" alt="<?php echo esc_attr($recover_site_name); ?>" style="display:block;max-width:200px;height:auto;border:0;" /></p>
                            <?php endif; ?>
                            <h1 style="margin:0 0 14px;font-size:23px;line-height:1.3;color:<?php echo esc_attr($recover_palette['heading']); ?>;">
                                <?php echo esc_html($recover_heading); ?>
                            </h1>
                            <!-- Ember underline: a small lit accent under the heading, grounding the warm world. -->
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
                                <tr>
                                    <td style="width:48px;height:3px;background:<?php echo esc_attr($recover_palette['accent']); ?>;border-radius:2px;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                            <p style="margin:0 0 8px;font-size:15px;line-height:1.65;color:<?php echo esc_attr($recover_palette['soft']); ?>;">
                                <?php echo nl2br(esc_html($recover_body)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by esc_html(); nl2br() only adds <br />. ?>
                            </p>
                        </td>
                    </tr>
                    <?php if (! empty($recover_items)) : ?>
                    <tr>
                        <td style="padding:8px 36px 16px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid <?php echo esc_attr($recover_palette['line']); ?>;">
                                <?php foreach ($recover_items as $recover_item) : ?>
                                <tr>
                                    <?php if ((string) $recover_item['image'] !== '') : ?>
                                    <td width="64" style="width:64px;padding:12px 12px 12px 0;border-bottom:1px solid <?php echo esc_attr($recover_palette['line']); ?>;vertical-align:middle;">
                                        <img src="<?php echo esc_url((string) $recover_item['image']); ?>" width="64" height="64" alt="<?php echo esc_attr((string) $recover_item['name']); ?>" style="display:block;width:64px;height:64px;border:0;border-radius:8px;object-fit:cover;" />
                                    </td>
                                    <?php endif; ?>
                                    <td style="padding:12px 12px 12px 0;border-bottom:1px solid <?php echo esc_attr($recover_palette['line']); ?>;vertical-align:middle;font-size:14px;line-height:1.4;color:<?php echo esc_attr($recover_palette['text']); ?>;">
                                        <?php echo esc_html((string) $recover_item['name']); ?>
                                        <br />
                                        <span style="font-size:12px;color:<?php echo esc_attr($recover_palette['muted']); ?>;">
                                            <?php
                                            printf(
                                                /* translators: %d: quantity of this product in the cart */
                                                esc_html__('Quantity: %d', 'plogins-recover'),
                                                (int) $recover_item['quantity'],
                                            );
                                            ?>
                                        </span>
                                    </td>
                                    <td align="right" style="padding:12px 0;border-bottom:1px solid <?php echo esc_attr($recover_palette['line']); ?>;vertical-align:middle;font-size:14px;color:<?php echo esc_attr($recover_palette['text']); ?>;white-space:nowrap;">
                                        <?php echo wp_kses_post((string) $recover_item['line_total']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if ($recover_total !== '') : ?>
                                <tr>
                                    <td colspan="2" style="padding:12px 12px 0 0;font-size:14px;font-weight:700;color:<?php echo esc_attr($recover_palette['heading']); ?>;">
                                        <?php echo esc_html__('Cart total', 'plogins-recover'); ?>
                                    </td>
                                    <td align="right" style="padding:12px 0 0;font-size:14px;font-weight:700;color:<?php echo esc_attr($recover_palette['heading']); ?>;white-space:nowrap;">
                                        <?php echo wp_kses_post($recover_total); ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td align="center" style="padding:8px 24px 4px;">
                            <?php if ($recover_palette['woo']) : ?>
                            <a href="<?php echo esc_url($recover_restore_url); ?>"
                               style="display:inline-block;background-color:<?php echo esc_attr($recover_palette['accent']); ?>;color:<?php echo esc_attr($recover_palette['button_text']); ?>;text-decoration:none;font-size:16px;font-weight:700;padding:15px 32px;border-radius:999px;margin:8px 0 4px;">
                                <?php echo esc_html($recover_button); ?>
                            </a>
                            <?php else : ?>
                            <!--
                              The lantern: a warm panel that reads as a window with the light
                              left on. Radial-gradient glow for capable clients; a solid warm
                              amber wash is the bulletproof fallback so the panel always feels lit.
                            -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:472px;">
                                <tr>
                                    <td align="center" style="background-color:#fff7ed;background-image:radial-gradient(120% 140% at 50% 0%, #ffedd5 0%, #fff7ed 55%, #ffffff 100%);border:1px solid #fed7aa;border-radius:14px;padding:26px 24px 28px;">
                                        <!-- A small kept-on lamp: the light the shop left burning. -->
                                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;">
                                            <tr>
                                                <td align="center" style="width:34px;height:34px;background-color:#fde68a;background-image:radial-gradient(circle at 50% 38%, #fef3c7 0%, #fde68a 45%, #fbbf24 100%);border-radius:999px;box-shadow:0 0 0 6px rgba(251,191,36,0.22);font-size:18px;line-height:34px;color:#b45309;">&#9728;</td>
                                            </tr>
                                        </table>
                                        <p style="margin:0 0 16px;font-size:13px;line-height:1.5;color:#9a3412;font-weight:600;letter-spacing:0.01em;">
                                            <?php echo esc_html__('We left the light on, your cart is still here.', 'plogins-recover'); ?>
                                        </p>
                                        <a href="<?php echo esc_url($recover_restore_url); ?>"
                                           style="display:inline-block;background-color:#a3360b;background-image:linear-gradient(180deg, #c44510 0%, #a3360b 100%);color:#ffffff;text-decoration:none;font-size:16px;font-weight:700;padding:15px 32px;border-radius:10px;box-shadow:0 6px 16px rgba(163,54,11,0.32);">
                                            <?php echo esc_html($recover_button); ?>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:14px 36px 4px;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:<?php echo esc_attr($recover_palette['muted']); ?>;">
                                <?php echo esc_html__('The link picks up right where you left off.', 'plogins-recover'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 36px 34px;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:<?php echo esc_attr($recover_palette['muted']); ?>;">
                                <?php
                                printf(
                                    /* translators: %s: site name */
                                    esc_html__('This message was sent by %s because a cart was left behind. If this was not you, you can safely ignore this email.', 'plogins-recover'),
                                    esc_html($recover_site_name),
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
