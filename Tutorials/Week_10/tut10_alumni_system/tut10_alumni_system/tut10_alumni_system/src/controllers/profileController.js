const Alumni = require('../models/Alumni');
const ProfileItem = require('../models/ProfileItem');
const Employment = require('../models/Employment');

function groupItems(items) {
  return ProfileItem.allowedTypes.reduce((groups, type) => {
    groups[type] = items.filter((item) => item.item_type === type);
    return groups;
  }, {});
}

async function showProfile(req, res, next) {
  try {
    const items = await ProfileItem.findByAlumniId(req.user.id);
    const employment = await Employment.findByAlumniId(req.user.id);
    res.render('profile/index', {
      title: 'My Alumni Profile',
      alumni: req.user,
      groupedItems: groupItems(items),
      employment
    });
  } catch (error) {
    next(error);
  }
}

function renderEditProfile(req, res) {
  res.render('profile/edit', { title: 'Edit Profile', alumni: req.user });
}

async function updateProfile(req, res, next) {
  try {
    await Alumni.updateProfile(req.user.id, req.body);
    req.flash('success', 'Profile updated successfully.');
    res.redirect('/profile');
  } catch (error) {
    next(error);
  }
}

async function uploadImage(req, res, next) {
  try {
    if (!req.file) {
      req.flash('error', 'Please select an image to upload.');
      return res.redirect('/profile');
    }
    await Alumni.updateProfileImage(req.user.id, req.file.filename);
    req.flash('success', 'Profile image uploaded successfully.');
    res.redirect('/profile');
  } catch (error) {
    next(error);
  }
}

function renderNewItem(req, res) {
  const itemType = ProfileItem.normalizeType(req.query.type);
  res.render('profile/item-form', {
    title: `Add ${itemType}`,
    item: { item_type: itemType },
    action: '/profile/items',
    allowedTypes: ProfileItem.allowedTypes
  });
}

async function createItem(req, res, next) {
  try {
    await ProfileItem.create(req.user.id, req.body);
    req.flash('success', 'Profile item added successfully.');
    res.redirect('/profile');
  } catch (error) {
    next(error);
  }
}

async function renderEditItem(req, res, next) {
  try {
    const item = await ProfileItem.findByIdAndAlumni(req.params.id, req.user.id);
    if (!item) {
      req.flash('error', 'Profile item not found.');
      return res.redirect('/profile');
    }
    res.render('profile/item-form', {
      title: `Edit ${item.item_type}`,
      item,
      action: `/profile/items/${item.id}/update`,
      allowedTypes: ProfileItem.allowedTypes
    });
  } catch (error) {
    next(error);
  }
}

async function updateItem(req, res, next) {
  try {
    await ProfileItem.update(req.params.id, req.user.id, req.body);
    req.flash('success', 'Profile item updated successfully.');
    res.redirect('/profile');
  } catch (error) {
    next(error);
  }
}

async function deleteItem(req, res, next) {
  try {
    await ProfileItem.remove(req.params.id, req.user.id);
    req.flash('success', 'Profile item deleted successfully.');
    res.redirect('/profile');
  } catch (error) {
    next(error);
  }
}

function renderNewEmployment(req, res) {
  res.render('profile/employment-form', {
    title: 'Add Employment History',
    job: {},
    action: '/profile/employment'
  });
}

async function createEmployment(req, res, next) {
  try {
    await Employment.create(req.user.id, req.body);
    req.flash('success', 'Employment history added successfully.');
    res.redirect('/profile');
  } catch (error) {
    next(error);
  }
}

async function renderEditEmployment(req, res, next) {
  try {
    const job = await Employment.findByIdAndAlumni(req.params.id, req.user.id);
    if (!job) {
      req.flash('error', 'Employment record not found.');
      return res.redirect('/profile');
    }
    res.render('profile/employment-form', {
      title: 'Edit Employment History',
      job,
      action: `/profile/employment/${job.id}/update`
    });
  } catch (error) {
    next(error);
  }
}

async function updateEmployment(req, res, next) {
  try {
    await Employment.update(req.params.id, req.user.id, req.body);
    req.flash('success', 'Employment history updated successfully.');
    res.redirect('/profile');
  } catch (error) {
    next(error);
  }
}

async function deleteEmployment(req, res, next) {
  try {
    await Employment.remove(req.params.id, req.user.id);
    req.flash('success', 'Employment history deleted successfully.');
    res.redirect('/profile');
  } catch (error) {
    next(error);
  }
}

module.exports = {
  showProfile,
  renderEditProfile,
  updateProfile,
  uploadImage,
  renderNewItem,
  createItem,
  renderEditItem,
  updateItem,
  deleteItem,
  renderNewEmployment,
  createEmployment,
  renderEditEmployment,
  updateEmployment,
  deleteEmployment
};
