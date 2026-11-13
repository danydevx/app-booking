import type { TypedSchema, TypedSchemaError } from 'vee-validate';
import type { ZodType } from 'zod';

/**
 * Minimal bridge between Zod 4 schemas and vee-validate's documented
 * `TypedSchema` contract.
 *
 * The former `@vee-validate/zod` adapter is pinned to Zod 3 and imports APIs
 * removed in Zod 4. This bridge only translates `safeParse` results; the
 * validation rules themselves stay in the Zod schemas.
 */
export function toZodTypedSchema<TInput extends Record<string, unknown>>(
    schema: ZodType<TInput>,
): TypedSchema<TInput> {
    return {
        __type: 'VVTypedSchema',
        async parse(values) {
            const result = await schema.safeParseAsync(values);

            if (result.success) {
                return {
                    value: result.data,
                    errors: [],
                };
            }

            return {
                errors: groupIssues(result.error.issues),
            };
        },
        cast(values) {
            const result = schema.safeParse(values);

            return result.success ? result.data : (values as TInput);
        },
    };
}

function groupIssues(
    issues: Array<{ path: PropertyKey[]; message: string }>,
): TypedSchemaError[] {
    const grouped = new Map<string, TypedSchemaError>();

    for (const issue of issues) {
        const path = normalizePath(issue.path);
        const existing = grouped.get(path);

        if (existing !== undefined) {
            existing.errors.push(issue.message);
            continue;
        }

        grouped.set(path, {
            path,
            errors: [issue.message],
        });
    }

    return [...grouped.values()];
}

function normalizePath(path: PropertyKey[]): string {
    return path.reduce<string>((result, segment, index) => {
        if (typeof segment === 'number') {
            return `${result}[${segment}]`;
        }

        const key = String(segment);

        if (index === 0) {
            return key;
        }

        return result === '' ? key : `${result}.${key}`;
    }, '');
}
