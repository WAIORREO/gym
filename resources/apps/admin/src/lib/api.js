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

export const useApi = (headers = {}) => {
	let base = PUBLIC_API_URL;

	// In Node.js server runtime (like on Render), read dynamically from process.env
	if (typeof process !== 'undefined' && process.env && process.env.PUBLIC_API_URL) {
		base = process.env.PUBLIC_API_URL;
	}

	if (!base) {
		base = 'http://localhost:8000';
	}

	base = base.replace(/\/+$/, '');

	return axios.create({
		headers,
		baseURL: base + '/api',
		timeout: 20000 // 20s timeout in case free tier server is waking up
	});
};
