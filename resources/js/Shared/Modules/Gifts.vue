<script setup>
import { ref } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { flash, isDark } from '@/methods';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import ContactSelector from '@/Shared/Form/ContactSelector.vue';
import ContactCard from '@/Shared/ContactCard.vue';
import { Gift } from 'lucide-vue-next';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const localOpenGifts = ref(props.data.open_gifts);
const localGivenGifts = ref(props.data.given_gifts);
const createGiftModalShown = ref(false);
const editedGiftId = ref(0);
const loadingState = ref('');
const errors = ref({});
const generalError = ref('');
const masks = ref({
  modelValue: 'YYYY-MM-DD',
});

const emptyForm = () => ({
  name: '',
  gift_occasion_id: '',
  gift_state_id: props.data.gift_states.length > 0 ? props.data.gift_states[0].id : '',
  estimated_price: '',
  currency_id: '',
  url: '',
  description: '',
  given_at: null,
  recipients: [],
});

const form = ref(emptyForm());

const currentContact = () => {
  return props.data.current_contact ? [props.data.current_contact] : [];
};

const resetErrors = () => {
  errors.value = {};
  generalError.value = '';
};

const fieldError = (field) => {
  return errors.value[field] ? errors.value[field][0] : null;
};

const showCreateGiftModal = () => {
  resetErrors();
  editedGiftId.value = 0;
  form.value = emptyForm();
  form.value.recipients = currentContact();
  createGiftModalShown.value = true;
};

const showEditGiftModal = (gift) => {
  resetErrors();
  createGiftModalShown.value = false;
  form.value = {
    name: gift.name,
    gift_occasion_id: gift.occasion ? gift.occasion.id : '',
    gift_state_id: gift.state ? gift.state.id : '',
    estimated_price: gift.estimated_price !== null ? gift.estimated_price : '',
    currency_id: gift.currency_id ?? '',
    url: gift.link ?? '',
    description: gift.description ?? '',
    given_at: gift.given_at,
    recipients: [...gift.recipients],
  };
  editedGiftId.value = gift.id;
};

const cancel = () => {
  createGiftModalShown.value = false;
  editedGiftId.value = 0;
  resetErrors();
};

const handleError = (error) => {
  loadingState.value = '';
  if (error.response && error.response.status === 422 && error.response.data.errors) {
    errors.value = error.response.data.errors;
  } else {
    generalError.value = trans('Oops! Something went wrong.');
  }
};

const removeGift = (giftId) => {
  localOpenGifts.value = localOpenGifts.value.filter((x) => x.id !== giftId);
  localGivenGifts.value = localGivenGifts.value.filter((x) => x.id !== giftId);
};

// the gift goes to the open list or to the gift history, depending on its stage.
// it is removed from this page when the contact is no longer one of its recipients.
const placeGift = (gift) => {
  removeGift(gift.id);

  const isRecipient = props.data.current_contact
    ? gift.recipients.some((recipient) => recipient.id === props.data.current_contact.id)
    : true;

  if (!isRecipient) {
    return;
  }

  if (gift.is_given) {
    localGivenGifts.value.unshift(gift);
  } else {
    localOpenGifts.value.unshift(gift);
  }
};

const submit = () => {
  resetErrors();
  loadingState.value = 'loading';

  axios
    .post(props.data.url.store, form.value)
    .then((response) => {
      flash(trans('The gift has been added'), 'success');
      placeGift(response.data.data);
      loadingState.value = '';
      createGiftModalShown.value = false;
    })
    .catch(handleError);
};

const update = (gift) => {
  resetErrors();
  loadingState.value = 'loading';

  axios
    .put(gift.url.update, form.value)
    .then((response) => {
      flash(trans('The gift has been updated'), 'success');
      placeGift(response.data.data);
      loadingState.value = '';
      editedGiftId.value = 0;
    })
    .catch(handleError);
};

const destroy = (gift) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios
      .delete(gift.url.destroy)
      .then(() => {
        flash(trans('The gift has been deleted'), 'success');
        removeGift(gift.id);
      })
      .catch(handleError);
  }
};
</script>

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <Gift class="h-4 w-4 text-gray-600" />

        <span class="font-semibold"> {{ $t('Gifts') }} </span>
      </div>
      <pretty-button
        v-if="data.can_edit"
        :text="$t('Add a gift')"
        :icon="'plus'"
        :class="'w-full sm:w-fit'"
        @click="showCreateGiftModal" />
    </div>

    <!-- the form to add or edit a gift -->
    <template v-for="mode in ['create', 'edit']" :key="mode">
      <form
        v-if="(mode === 'create' && createGiftModalShown) || (mode === 'edit' && editedGiftId !== 0)"
        novalidate
        class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
        @submit.prevent="
          mode === 'create'
            ? submit()
            : update([...localOpenGifts, ...localGivenGifts].find((x) => x.id === editedGiftId))
        ">
        <div class="border-b border-gray-200 dark:border-gray-700">
          <!-- what it is -->
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <text-input
              v-model="form.name"
              :label="$t('What is the gift?')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="cancel" />
            <p v-if="fieldError('name')" class="mt-1 text-sm text-red-600">{{ fieldError('name') }}</p>
          </div>

          <!-- recipients -->
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <contact-selector
              v-model="form.recipients"
              :search-url="layoutData.vault.url.search_contacts_only"
              :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
              :display-most-consulted-contacts="false"
              :label="$t('Who is the gift for?')"
              :add-multiple-contacts="true"
              :required="true" />
            <p v-if="fieldError('recipients')" class="mt-1 text-sm text-red-600">{{ fieldError('recipients') }}</p>
          </div>

          <!-- occasion + stage -->
          <div class="flex flex-wrap gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
            <dropdown
              v-model="form.gift_occasion_id"
              :data="data.gift_occasions"
              :required="false"
              :placeholder="$t('No occasion')"
              :dropdown-class="'block'"
              :label="$t('Occasion')" />

            <dropdown
              v-model="form.gift_state_id"
              :data="data.gift_states"
              :required="true"
              :dropdown-class="'block'"
              :label="$t('Gift stage')" />
          </div>

          <!-- price + currency -->
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <div class="flex">
              <text-input
                v-model="form.estimated_price"
                :label="$t('Rough price')"
                :help="$t('A whole amount, like 40')"
                :type="'number'"
                :step="'1'"
                :input-class="'w-full'"
                :required="false"
                :min="0"
                :autocomplete="false"
                @esc-key-pressed="cancel" />

              <dropdown
                v-model="form.currency_id"
                :data="data.currencies"
                :required="false"
                :class="'mb-5 ms-3'"
                :placeholder="$t('Choose a value')"
                :dropdown-class="'block'"
                :label="$t('Currency')" />
            </div>
            <p v-if="fieldError('estimated_price')" class="mt-1 text-sm text-red-600">
              {{ fieldError('estimated_price') }}
            </p>
          </div>

          <!-- link -->
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <text-input
              v-model="form.url"
              :label="$t('Link')"
              :type="'text'"
              :input-class="'block w-full'"
              :required="false"
              :autocomplete="false"
              :maxlength="2048"
              @esc-key-pressed="cancel" />
            <p v-if="fieldError('url')" class="mt-1 text-sm text-red-600">{{ fieldError('url') }}</p>
          </div>

          <!-- date given -->
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <p class="mb-2 block text-sm">
              {{ $t('Date given') }}
              <span class="optional-badge rounded-xs px-[3px] py-px text-xs">{{ $t('optional') }}</span>
            </p>

            <DatePicker
              v-model.string="form.given_at"
              class="inline-block h-full"
              :masks="masks"
              :locale="$page.props.auth.user?.locale_ietf"
              :is-dark="isDark()">
              <template #default="{ inputValue, inputEvents }">
                <input
                  class="rounded-xs border bg-white px-2 py-1 dark:bg-gray-900"
                  :value="inputValue"
                  v-on="inputEvents" />
              </template>
            </DatePicker>
            <span
              v-if="form.given_at"
              class="ms-3 cursor-pointer text-sm text-blue-500 hover:underline"
              @click="form.given_at = null">
              {{ $t('Clear') }}
            </span>
            <p class="mt-1 text-xs text-gray-500">
              {{ $t('Filled in with today’s date when the gift reaches the last gift stage.') }}
            </p>
            <p v-if="fieldError('given_at')" class="mt-1 text-sm text-red-600">{{ fieldError('given_at') }}</p>
          </div>

          <!-- note -->
          <div class="p-5">
            <text-area v-model="form.description" :label="$t('Note')" :textarea-class="'block w-full'" />
          </div>
        </div>

        <div v-if="generalError !== ''" class="border-b p-3 text-red-600">{{ generalError }}</div>

        <div class="flex justify-between p-5">
          <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="cancel" />
          <pretty-button
            :text="mode === 'create' ? $t('Add gift') : $t('Save')"
            :state="loadingState"
            :icon="'check'"
            :class="'save'" />
        </div>
      </form>
    </template>

    <!-- list of gifts: open gifts, then the gift history -->
    <template v-for="list in ['open', 'given']" :key="list">
      <p v-if="list === 'given'" class="mb-2 mt-6 text-sm font-semibold text-gray-600 dark:text-gray-400">
        {{ $t('Gift history') }}
      </p>

      <p
        v-if="list === 'open' && localOpenGifts.length === 0"
        class="mb-3 rounded-lg border border-gray-200 bg-white p-3 text-center text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900">
        {{ $t('There are no gift ideas yet.') }}
      </p>

      <p
        v-if="list === 'given' && localGivenGifts.length === 0"
        class="mb-6 rounded-lg border border-gray-200 bg-white p-3 text-center text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900">
        {{ $t('No gift has been given yet.') }}
      </p>

      <div
        v-for="gift in list === 'open' ? localOpenGifts : localGivenGifts"
        v-show="editedGiftId !== gift.id"
        :key="gift.id"
        class="mb-3 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="px-3 py-2" :class="data.can_edit ? 'border-b border-gray-200 dark:border-gray-700' : ''">
          <div class="flex items-center justify-between">
            <span class="font-medium">
              <span v-if="gift.price_formatted" class="me-2">{{ gift.price_formatted }} • </span>
              {{ gift.name }}
            </span>
            <span v-if="gift.given_at_human_format" class="text-sm text-gray-500">
              {{ gift.given_at_human_format }}
            </span>
          </div>

          <div class="mt-1 flex flex-wrap items-center gap-2 text-sm">
            <span
              v-if="gift.state"
              class="rounded-full bg-sky-400/10 px-2 py-0.5 text-xs text-sky-600 dark:bg-sky-600/10 dark:text-sky-400">
              {{ gift.state.name }}
            </span>
            <span
              v-if="gift.occasion"
              class="rounded-full bg-amber-400/10 px-2 py-0.5 text-xs text-amber-700 dark:bg-amber-600/10 dark:text-amber-400">
              {{ gift.occasion.name }}
            </span>
          </div>

          <!-- the link is shown as a plain web address: Monica never visits it -->
          <p v-if="gift.link" class="mt-1 break-all text-sm">
            <a
              :href="gift.link"
              target="_blank"
              rel="noopener noreferrer nofollow"
              class="text-blue-500 hover:underline">
              {{ gift.link }}
            </a>
          </p>

          <p v-if="gift.description" class="mt-1 whitespace-pre-line text-sm text-gray-600 dark:text-gray-400">
            {{ gift.description }}
          </p>

          <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
            <span class="text-gray-500">{{ $t('For:') }}</span>
            <div v-for="recipient in gift.recipients" :key="recipient.id">
              <contact-card :contact="recipient" :avatar-classes="'h-5 w-5 rounded-full me-1'" :display-name="true" />
            </div>
          </div>
        </div>

        <!-- actions -->
        <div v-if="data.can_edit" class="px-3 py-2">
          <ul class="text-sm">
            <li class="me-4 inline cursor-pointer text-blue-500 hover:underline" @click="showEditGiftModal(gift)">
              {{ $t('Edit') }}
            </li>
            <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(gift)">
              {{ $t('Delete') }}
            </li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>
