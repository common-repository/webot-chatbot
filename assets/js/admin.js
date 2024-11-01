(function($){

	function WebotPlugin(){
		this.endpoint = webot_settings.endpoint;
		this.hostname = null;
		this.status = 'n/a';
		this.error = '';
		this.loading = false;
		this.init();
	}

	WebotPlugin.prototype = {
		init(){
			this.registerEvents();
			this.checkFields();
			$('.webot-color-picker').wpColorPicker();
		},
		registerEvents(){
			$('#webot-check-domain-status').on('click', this.check.bind(this));
		},
		checkFields(){
			this.hostname = $('input[name=hostname]').val() || this.hostname;
			return this.hostname;
		},
		check(){
			this.checkFields();
			if(!this.hostname || this.loading) return;
			this.setLoading();
			let nonce = $('#webot-check-domain-status').data('nonce') || '';
			$.post(this.endpoint, {action: 'webot_ajax', a: 'check', hostname: this.hostname, _wpnonce: nonce}, resp => {
				if(resp.success){
					let data = resp.data || {status: "", error: ""};
					this.hostname = data.hostname || this.hostname;
					this.status = data.status || 'n/a';
					this.error = data.error || (this.status == 'error' ? 'Unknown error' : '');
					this.updateUI();
				}
			}, 'json').always(() => {
				this.clearLoading();
			});
		},
		setLoading(){
			this.loading = true;
			$('#webot-check-domain-status').prop('disabled', true).addClass('disabled');
		},
		clearLoading(){
			this.loading = false;
			$('#webot-check-domain-status').prop('disabled', false).removeClass('disabled');
		},
		updateUI(){
			$('#webot-domain-status').html(this.status);
			$('#webot-domain-error').html(this.error);
			if(this.error){
				$('.webot-domain-status-wrap .error-wrap').removeClass('hidden');
				$('#webot-domain-status').addClass('negative-text');
			} else {
				$('.webot-domain-status-wrap .error-wrap').addClass('hidden');
				$('#webot-domain-status').removeClass('negative-text');
			}
		}
	};

	$(document).ready(function(){
		const WEBOT_PLUGIN = new WebotPlugin();
	});

})(jQuery);