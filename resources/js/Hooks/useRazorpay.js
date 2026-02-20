import { useState, useEffect, useCallback } from 'react';
import { router } from '@inertiajs/react';

/**
 * Reusable hook for Razorpay payment integration.
 *
 * @param {Object} options
 * @param {boolean} options.isProduction - Whether the app is in production mode (controls script loading)
 * @param {Object} options.user - Current authenticated user { name, email }
 * @param {string} options.appName - Application name shown in Razorpay popup
 * @param {string} options.themeColor - Brand color for Razorpay popup
 * @param {Function} options.onSuccess - Callback after successful verification redirect
 * @param {Function} options.onError - Callback on error
 */
export default function useRazorpay({
    isProduction = true,
    user = {},
    appName = 'Kutoot',
    themeColor = '#f08c10',
    onSuccess = null,
    onError = null,
} = {}) {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState(null);

    // Load Razorpay script in production mode
    useEffect(() => {
        if (!isProduction) return;

        const existingScript = document.querySelector('script[src="https://checkout.razorpay.com/v1/checkout.js"]');
        if (existingScript) return;

        const script = document.createElement('script');
        script.src = 'https://checkout.razorpay.com/v1/checkout.js';
        script.async = true;
        document.body.appendChild(script);

        return () => {
            if (document.body.contains(script)) {
                document.body.removeChild(script);
            }
        };
    }, [isProduction]);

    /**
     * Open the Razorpay checkout popup with the given order details.
     *
     * @param {Object} order - Razorpay order object from backend { id, amount, currency, key }
     * @param {string} verifyRoute - Named route for payment verification
     * @param {Object} extraData - Additional data to send with verification (e.g. plan_id)
     * @param {string} description - Payment description shown in popup
     */
    const openCheckout = useCallback((order, verifyRoute, extraData = {}, description = 'Payment') => {
        if (!window.Razorpay) {
            const err = 'Razorpay SDK not loaded. Please refresh the page.';
            setError(err);
            onError?.(err);
            return;
        }

        const options = {
            key: order.key,
            amount: order.amount,
            currency: order.currency,
            name: appName,
            description,
            order_id: order.id,
            handler: function (response) {
                setIsLoading(true);
                router.post(verifyRoute, {
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_signature: response.razorpay_signature,
                    ...extraData,
                }, {
                    onSuccess: () => {
                        setIsLoading(false);
                        onSuccess?.();
                    },
                    onError: (errs) => {
                        setIsLoading(false);
                        setError('Payment verification failed');
                        onError?.(errs);
                    },
                });
            },
            prefill: {
                name: user?.name || '',
                email: user?.email || '',
            },
            theme: {
                color: themeColor,
            },
            modal: {
                ondismiss: () => {
                    setIsLoading(false);
                },
            },
        };

        const rzp = new window.Razorpay(options);
        rzp.open();
    }, [appName, themeColor, user, onSuccess, onError]);

    /**
     * Initiate a payment flow.
     * In non-production mode, uses standard Inertia POST (backend auto-completes).
     * In production mode, fetches the order via JSON and opens Razorpay popup.
     *
     * @param {Object} params
     * @param {string} params.orderRoute - URL to POST to create the order
     * @param {Object} params.orderData - Form data for the order creation
     * @param {string} params.verifyRoute - Named route for payment verification
     * @param {Object} params.extraVerifyData - Additional data for verification
     * @param {string} params.description - Payment description
     * @param {Function} params.onDebugSuccess - Callback for debug mode success
     */
    const initiatePayment = useCallback(async ({
        orderRoute,
        orderData,
        verifyRoute,
        extraVerifyData = {},
        description = 'Payment',
        onDebugSuccess = null,
    }) => {
        setIsLoading(true);
        setError(null);

        // Non-production mode: standard Inertia POST (backend auto-completes)
        if (!isProduction) {
            router.post(orderRoute, orderData, {
                onSuccess: () => {
                    setIsLoading(false);
                    onDebugSuccess?.();
                },
                onError: (errs) => {
                    setIsLoading(false);
                    setError('Request failed');
                    onError?.(errs);
                },
            });
            return;
        }

        // Production mode: fetch order via JSON, then open Razorpay popup
        try {
            const response = await fetch(orderRoute, {
                method: 'POST',
                // make sure the browser sends the session cookie when the URL
                // is an absolute one generated by Ziggy's `route()` helper.
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(orderData),
            });

            const result = await response.json();

            if (!response.ok) {
                const errMsg = result.error || 'Something went wrong';
                setError(errMsg);
                setIsLoading(false);
                onError?.(errMsg);
                return;
            }

            setIsLoading(false);
            openCheckout(result.order, verifyRoute, extraVerifyData, description);
        } catch (err) {
            setIsLoading(false);
            const errMsg = 'Payment initiation failed. Please try again.';
            setError(errMsg);
            onError?.(errMsg);
        }
    }, [isProduction, openCheckout, onError]);

    return {
        initiatePayment,
        openCheckout,
        isLoading,
        error,
        clearError: () => setError(null),
    };
}
