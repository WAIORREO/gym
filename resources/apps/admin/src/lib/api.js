import { browser } from '$app/environment';
import { PUBLIC_API_URL } from '$env/static/public';
import axios from 'axios';

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
	let base = '';

	if (typeof process !== 'undefined' && process.env && process.env.PUBLIC_API_URL) {
		base = process.env.PUBLIC_API_URL;
	} else if (PUBLIC_API_URL) {
		base = PUBLIC_API_URL;
	}

	const baseURL = normalizeApiBaseUrl(base);

	return axios.create({
		headers,
		baseURL,
		timeout: 30000 // 30s timeout in case free tier service is waking up
	});
};
