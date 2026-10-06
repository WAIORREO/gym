// @ts-nocheck
import { json } from '@sveltejs/kit';
import { useApi } from '$lib/api.js';

export const POST = async ({ request, cookies }) => {
	try {
		const body = await request.json();
		const api = useApi();

		const response = await api
			.post('/auth/login', body)
			.then((res) => res.data)
			.catch((error) => {
				const errorData = error.response ? error.response.data : null;
				let message = errorData?.message || error.message || 'تعذر الاتصال بخادم الـ API';

				if (error.code === 'ECONNREFUSED' || error.message?.includes('ECONNREFUSED')) {
					message = 'سيرفر الـ API غير متصل أو لم يكتمل تشغيله بعد. تأكد من تشغيل gym-api على Render';
				} else if (error.message?.includes('Invalid URL')) {
					message = 'رابط الـ API غير صالح. يرجى مراجعة PUBLIC_API_URL في إعدادات Render';
				} else if (error.code === 'ETIMEDOUT' || error.message?.includes('timeout')) {
					message = 'انتهت مهلة الاتصال بالـ API. قد يكون السيرفر في وضع النوم (Spinning up)';
				}

				return { errors: true, message, details: errorData };
			});

		if (response.errors || !response.access_token) {
			return json(
				{
					success: false,
					message: response.message || 'بيانات الدخول غير صحيحة أو تعذر الاتصال بالخادم',
					...response
				},
				{ status: 401 }
			);
		}

		if (!response.user?.is_admin) {
			return json(
				{
					success: false,
					message: 'هذا الحساب ليس لديه صلاحيات الإدارة (Admin only)'
				},
				{ status: 403 }
			);
		}

		// Set cookie
		cookies.set('token', response.access_token, {
			path: '/',
			httpOnly: false,
			sameSite: 'lax',
			maxAge: 60 * 60 * 24 * 30 // 30 days
		});

		return json({
			success: true,
			access_token: response.access_token,
			user: response.user
		});
	} catch (error) {
		return json(
			{
				success: false,
				message: error.message || 'خطأ داخلي في الخادم'
			},
			{ status: 500 }
		);
	}
};
