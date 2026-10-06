// Web Audio API Sound Synthesizer for Gym Gate / RFID Access
let audioCtx = null;

const getAudioContext = () => {
	if (typeof window === 'undefined') return null;
	if (!audioCtx) {
		const AudioContextClass = window.AudioContext || window.webkitAudioContext;
		if (AudioContextClass) {
			audioCtx = new AudioContextClass();
		}
	}
	if (audioCtx && audioCtx.state === 'suspended') {
		audioCtx.resume();
	}
	return audioCtx;
};

/**
 * Play a high-tech pleasant chime for ACCESS GRANTED
 */
export const playAccessGrantedSound = () => {
	try {
		const ctx = getAudioContext();
		if (!ctx) return;

		const now = ctx.currentTime;

		// First tone (E5: ~659Hz)
		const osc1 = ctx.createOscillator();
		const gain1 = ctx.createGain();
		osc1.type = 'sine';
		osc1.frequency.setValueAtTime(659.25, now);
		gain1.gain.setValueAtTime(0.2, now);
		gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
		osc1.connect(gain1);
		gain1.connect(ctx.destination);
		osc1.start(now);
		osc1.stop(now + 0.25);

		// Second tone higher (B5: ~987.77Hz)
		const osc2 = ctx.createOscillator();
		const gain2 = ctx.createGain();
		osc2.type = 'sine';
		osc2.frequency.setValueAtTime(987.77, now + 0.12);
		gain2.gain.setValueAtTime(0.25, now + 0.12);
		gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.45);
		osc2.connect(gain2);
		gain2.connect(ctx.destination);
		osc2.start(now + 0.12);
		osc2.stop(now + 0.45);

		// Third high harmonic (E6: ~1318Hz)
		const osc3 = ctx.createOscillator();
		const gain3 = ctx.createGain();
		osc3.type = 'triangle';
		osc3.frequency.setValueAtTime(1318.51, now + 0.22);
		gain3.gain.setValueAtTime(0.2, now + 0.22);
		gain3.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
		osc3.connect(gain3);
		gain3.connect(ctx.destination);
		osc3.start(now + 0.22);
		osc3.stop(now + 0.6);
	} catch (e) {
		console.warn('Audio playback error', e);
	}
};

/**
 * Play a clear warning buzzer for ACCESS DENIED
 */
export const playAccessDeniedSound = () => {
	try {
		const ctx = getAudioContext();
		if (!ctx) return;

		const now = ctx.currentTime;

		// Buzz 1
		const osc1 = ctx.createOscillator();
		const gain1 = ctx.createGain();
		osc1.type = 'sawtooth';
		osc1.frequency.setValueAtTime(140, now);
		gain1.gain.setValueAtTime(0.25, now);
		gain1.gain.exponentialRampToValueAtTime(0.01, now + 0.18);
		osc1.connect(gain1);
		gain1.connect(ctx.destination);
		osc1.start(now);
		osc1.stop(now + 0.18);

		// Buzz 2
		const osc2 = ctx.createOscillator();
		const gain2 = ctx.createGain();
		osc2.type = 'sawtooth';
		osc2.frequency.setValueAtTime(115, now + 0.22);
		gain2.gain.setValueAtTime(0.3, now + 0.22);
		gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.48);
		osc2.connect(gain2);
		gain2.connect(ctx.destination);
		osc2.start(now + 0.22);
		osc2.stop(now + 0.48);
	} catch (e) {
		console.warn('Audio playback error', e);
	}
};
