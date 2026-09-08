import moment, { type Moment, type MomentInput } from 'moment';
import 'moment/dist/locale/pt-br';

/**
 * Shared Moment instance already configured for pt-BR.
 *
 * Maintains a single configuration point for components that still depend
 * on Moment-based parsing/formats.
 */

const instance: typeof moment = moment;
instance.locale('pt-br');

export default instance;
