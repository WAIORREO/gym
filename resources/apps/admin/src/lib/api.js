import { browser } from '$app/environment';
import { env } from '$env/dynamic/public';
import axios from 'axios';

/** @param {any} error */
export const getErrorMessage = (error) => {
	let message = error?.message || 'Unknown error';

	if (error && error.response && error.response.data && error.response.data.message) {
		message = error.response.data.message;
	}

	return message;
};

export const getBearerToken = () => {
	if (browser) {
		const token = document.getElementById('access_token');
		const value = token ? token.getAttribute('content') : undefined;
		if (value && value !== 'undefined' && value !== 'null' && value !== '') {
			return `Bearer ${value}`;
		}

		try {
			const local = localStorage.getItem('access_token');
			if (local) return `Bearer ${local}`;
		} catch (e) {}
	}
	return undefined;
};

/** @param {unknown} raw */
export const normalizeApiBaseUrl = (raw) => {
	let str = typeof raw === 'string' ? raw.trim() : '';

	// Remove wrapping quotes if entered in Render environment UI
	str = str.replace(/^["']+|["']+$/g, '').trim();

	if (!str) {
		return 'http://localhost:8000/api';
	}

	// Auto-prepend https:// if user pasted just the domain (e.g. gym-api.onrender.com)
	if (!/^https?:\/\//i.test(str)) {
		str = 'https://' + str;
	}

	// Remove trailing slashes
	str = str.replace(/\/+$/, '');

	// Ensure /api suffix
	if (!str.endsWith('/api')) {
		str = str + '/api';
	}

	return str;
};

export const useApi = (headers = {}) => {
	const base = env.PUBLIC_API_URL || '';

	const baseURL = normalizeApiBaseUrl(base);

	const client = axios.create({
		headers,
		baseURL,
		timeout: 30000 // 30s timeout in case free tier service is waking up
	});
 client.interceptors.request.use(config => {
  if (typeof window !== 'undefined') config.headers['Accept-Language'] = localStorage.getItem('laragym_lang') || 'ar';
  return config;
 });
 return client;
};
