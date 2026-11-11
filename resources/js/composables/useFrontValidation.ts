import { toTypedSchema } from '@vee-validate/zod';
import { useForm as useVeeForm } from 'vee-validate';
import type { ZodType } from 'zod';

type InertiaFormLike = {
    setError: (errors: never) => void;
    clearErrors: () => void;
};

/**
 * Validate an Inertia form with a Zod schema before sending it to the server.
 *
 * The form keeps using Inertia for submission; VeeValidate + Zod provides
 * immediate client-side feedback and the backend remains the source of truth.
 */
export function useFrontValidation<T extends Record<string, unknown>>(
    schema: ZodType<T>,
    values: () => T,
) {
    const validation = useVeeForm<T>({
        validationSchema: toTypedSchema(schema),
        initialValues: values() as never,
    });

    async function validate(form: InertiaFormLike): Promise<boolean> {
        validation.setValues(values() as never);

        const result = await validation.validate();

        if (result.valid) {
            form.clearErrors();

            return true;
        }

        const errors: Record<string, string> = {};

        for (const [field, message] of Object.entries(result.errors)) {
            if (typeof message === 'string' && message !== '') {
                errors[field] = message;
            }
        }

        form.setError(errors as never);

        return false;
    }

    return { validate };
}
