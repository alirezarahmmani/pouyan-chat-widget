<?php

/** Admin settings view. @package AIChatWidget */
if (! defined('ABSPATH')) {
	exit;
}
?>
<div class="wrap acw-admin" dir="rtl">
	<header class="acw-admin__header">
		<div>
			<p class="acw-admin__eyebrow"><?php esc_html_e('اتصال امن به سرویس', 'ai-chat-widget'); ?></p>
			<h1><?php esc_html_e('ویجت چت هوشمند', 'ai-chat-widget'); ?></h1>
			<p><?php esc_html_e('حساب خود را متصل کنید و سپس ظاهر و رفتار چت را همین‌جا در وردپرس تنظیم کنید.', 'ai-chat-widget'); ?></p>
		</div>
		<span class="acw-status <?php echo $data['connected'] ? 'is-connected' : 'is-disconnected'; ?>"><span aria-hidden="true"></span><?php echo $data['connected'] ? esc_html__('متصل', 'ai-chat-widget') : esc_html__('متصل نیست', 'ai-chat-widget'); ?></span>
	</header>
	<?php settings_errors(); ?>
	<div class="acw-layout">
	<form class="acw-layout__form" action="options.php" method="post" autocomplete="off">
		<?php settings_fields('ai_chat_widget_settings_group'); ?>
		<section class="acw-card" aria-labelledby="acw-connection-title">
			<div class="acw-card__number" aria-hidden="true">01</div>
			<div class="acw-card__content">
				<h2 id="acw-connection-title"><?php esc_html_e('اتصال به سرویس', 'ai-chat-widget'); ?></h2>
				<p><?php esc_html_e('کلید ذخیره‌شده با کلیدهای امنیتی وردپرس رمزنگاری می‌شود، فقط در سمت سرور استفاده می‌شود و هرگز در صفحه نمایش داده نمی‌شود.', 'ai-chat-widget'); ?></p>
				<label for="ai-chat-widget-api-key"><?php esc_html_e('کلید API', 'ai-chat-widget'); ?></label>
				<input id="ai-chat-widget-api-key" name="ai_chat_widget_api_key" type="password" value="" dir="ltr" class="regular-text" maxlength="512" spellcheck="false" autocomplete="new-password" placeholder="sk_live_••••••••••••">
				<p class="description"><?php echo $data['connected'] ? esc_html__('برای حفظ کلید فعلی، این فیلد را خالی بگذارید. کلید جدید پیش از جایگزینی اعتبارسنجی می‌شود.', 'ai-chat-widget') : esc_html__('کلید پیش از ذخیره، با سرویس اعتبارسنجی می‌شود.', 'ai-chat-widget'); ?></p>
			</div>
		</section>
		<section class="acw-card" aria-labelledby="acw-experience-title">
			<div class="acw-card__number" aria-hidden="true">02</div>
			<div class="acw-card__content">
				<h2 id="acw-experience-title"><?php esc_html_e('تنظیم ظاهر و تجربه کاربری', 'ai-chat-widget'); ?></h2>
				<p><?php esc_html_e('این تنظیمات فقط در وردپرس ذخیره می‌شوند و از سرویس دریافت نمی‌شوند.', 'ai-chat-widget'); ?></p>
				<label class="acw-toggle"><input type="checkbox" name="ai_chat_widget_settings[enabled]" value="1" <?php checked(! empty($data['widget']['enabled'])); ?>><span><?php esc_html_e('نمایش ویجت وقتی اتصال API برقرار است', 'ai-chat-widget'); ?></span></label>
				<label class="acw-toggle"><input type="checkbox" name="ai_chat_widget_settings[show_without_api]" value="1" <?php checked(! empty($data['widget']['show_without_api'])); ?>><span><?php esc_html_e('نمایش ویجت وقتی API متصل نیست (کادر پیام غیرفعال می‌ماند)', 'ai-chat-widget'); ?></span></label>
				<div class="acw-field-grid">
					<div><label for="acw-title"><?php esc_html_e('عنوان ویجت', 'ai-chat-widget'); ?></label><input id="acw-title" name="ai_chat_widget_settings[title]" type="text" dir="auto" value="<?php echo esc_attr($data['widget']['title']); ?>" maxlength="80"></div>
					<div><label for="acw-color"><?php esc_html_e('رنگ اصلی', 'ai-chat-widget'); ?></label>
						<div class="acw-color-field"><input id="acw-color" name="ai_chat_widget_settings[primary_color]" type="color" value="<?php echo esc_attr($data['widget']['primary_color']); ?>"><output for="acw-color"><?php echo esc_html(strtoupper($data['widget']['primary_color'])); ?></output></div>
					</div>
				</div>
				<label for="acw-welcome"><?php esc_html_e('پیام خوش‌آمدگویی', 'ai-chat-widget'); ?></label><textarea id="acw-welcome" name="ai_chat_widget_settings[welcome]" rows="3" dir="auto" maxlength="280"><?php echo esc_textarea($data['widget']['welcome']); ?></textarea>
				<fieldset>
					<legend><?php esc_html_e('موقعیت در صفحه', 'ai-chat-widget'); ?></legend>
					<div class="acw-position-options">
						<label><input type="radio" name="ai_chat_widget_settings[position]" value="right" <?php checked('right', $data['widget']['position']); ?>><span><?php esc_html_e('پایین راست', 'ai-chat-widget'); ?></span></label>
						<label><input type="radio" name="ai_chat_widget_settings[position]" value="left" <?php checked('left', $data['widget']['position']); ?>><span><?php esc_html_e('پایین چپ', 'ai-chat-widget'); ?></span></label>
					</div>
				</fieldset>
				<fieldset>
					<legend><?php esc_html_e('جهت چیدمان', 'ai-chat-widget'); ?></legend>
					<div class="acw-direction-options">
						<?php
						$acw_directions = array(
							'ltr' => array(__('چپ به راست', 'ai-chat-widget'), __('انگلیسی و سایر زبان‌های چپ‌به‌راست', 'ai-chat-widget')),
							'rtl' => array(__('راست به چپ', 'ai-chat-widget'), __('فارسی، عربی، عبری', 'ai-chat-widget')),
						);
						foreach ($acw_directions as $acw_dir => $acw_labels) :
						?>
							<label>
								<input type="radio" name="ai_chat_widget_settings[direction]" value="<?php echo esc_attr($acw_dir); ?>" <?php checked($acw_dir, $data['widget']['direction']); ?>>
								<span class="acw-direction-card">
									<span class="acw-direction-card__mock" dir="<?php echo esc_attr($acw_dir); ?>" aria-hidden="true"><i class="is-head"><b></b><em></em></i><i class="is-in"></i><i class="is-out"></i></span>
									<strong><?php echo esc_html($acw_labels[0]); ?> <small><?php echo esc_html(strtoupper($acw_dir)); ?></small></strong>
									<span><?php echo esc_html($acw_labels[1]); ?></span>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			</div>
		</section>
		<?php submit_button(__('ذخیره تغییرات', 'ai-chat-widget'), 'primary acw-save'); ?>
	</form>
	<aside class="acw-preview" aria-label="<?php esc_attr_e('پیش‌نمایش زنده', 'ai-chat-widget'); ?>">
		<div class="acw-preview__label"><span><?php esc_html_e('پیش‌نمایش زنده', 'ai-chat-widget'); ?></span><small><?php esc_html_e('با هر تغییر به‌روز می‌شود؛ برای اعمال در سایت ذخیره کنید', 'ai-chat-widget'); ?></small></div>
		<div class="acw-preview__browser">
			<div class="acw-preview__bar" aria-hidden="true"><i></i><i></i><i></i><span><?php echo esc_html(wp_parse_url(home_url(), PHP_URL_HOST)); ?></span></div>
			<div class="acw-preview__stage">
				<?php
				$settings    = $data['widget'];
				$acw_preview = true;
				include AI_CHAT_WIDGET_PATH . 'templates/widget/chat-widget.php';
				?>
			</div>
		</div>
	</aside>
	</div>
</div>