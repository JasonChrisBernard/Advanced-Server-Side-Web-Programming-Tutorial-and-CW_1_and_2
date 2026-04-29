// Import the profile model that reads and writes the alumni profile tables.
const AlumniProfile = require("../models/alumniProfileModel");
// Import shared sanitizers and URL validation helpers for safer profile data.
const {
  isValidOptionalUrl,
  sanitizeMultilineText,
  sanitizeSingleLineText,
} = require("../utils/validators");

// Convert a form field into an array so one value and many values are handled the same way.
function toArray(value) {
  if (Array.isArray(value)) {
    return value;
  }

  if (value === undefined || value === null || value === "") {
    return [];
  }

  return [value];
}

// Build a clean array of degree rows from parallel HTML form arrays.
function buildDegrees(body) {
  const titles = toArray(body.degreeTitle);
  const institutions = toArray(body.degreeInstitution);
  const urls = toArray(body.degreeUrl);
  const completionDates = toArray(body.degreeCompletionDate);

  return titles
    .map((title, index) => ({
      title: sanitizeSingleLineText(title, 140),
      institution_name: sanitizeSingleLineText(institutions[index], 140),
      official_url: String(urls[index] || "").trim(),
      completion_date: String(completionDates[index] || "").trim(),
    }))
    .filter((row) => row.title || row.institution_name || row.official_url || row.completion_date)
    .map((row) => ({
      ...row,
      title: row.title || "Untitled degree",
    }));
}

// Build a clean array of certification rows from the form fields.
function buildCertifications(body) {
  const titles = toArray(body.certificationTitle);
  const providers = toArray(body.certificationProvider);
  const urls = toArray(body.certificationUrl);
  const completionDates = toArray(body.certificationCompletionDate);

  return titles
    .map((title, index) => ({
      title: sanitizeSingleLineText(title, 140),
      provider_name: sanitizeSingleLineText(providers[index], 140),
      official_url: String(urls[index] || "").trim(),
      completion_date: String(completionDates[index] || "").trim(),
    }))
    .filter((row) => row.title || row.provider_name || row.official_url || row.completion_date)
    .map((row) => ({
      ...row,
      title: row.title || "Untitled certification",
    }));
}

// Build a clean array of licence rows from the form fields.
function buildLicenses(body) {
  const titles = toArray(body.licenseTitle);
  const awardingBodies = toArray(body.licenseAwardingBody);
  const urls = toArray(body.licenseUrl);
  const completionDates = toArray(body.licenseCompletionDate);

  return titles
    .map((title, index) => ({
      title: sanitizeSingleLineText(title, 140),
      awarding_body: sanitizeSingleLineText(awardingBodies[index], 140),
      official_url: String(urls[index] || "").trim(),
      completion_date: String(completionDates[index] || "").trim(),
    }))
    .filter((row) => row.title || row.awarding_body || row.official_url || row.completion_date)
    .map((row) => ({
      ...row,
      title: row.title || "Untitled licence",
    }));
}

// Build a clean array of short-course rows from the form fields.
function buildCourses(body) {
  const titles = toArray(body.courseTitle);
  const providers = toArray(body.courseProvider);
  const urls = toArray(body.courseUrl);
  const completionDates = toArray(body.courseCompletionDate);

  return titles
    .map((title, index) => ({
      title: sanitizeSingleLineText(title, 140),
      provider_name: sanitizeSingleLineText(providers[index], 140),
      official_url: String(urls[index] || "").trim(),
      completion_date: String(completionDates[index] || "").trim(),
    }))
    .filter((row) => row.title || row.provider_name || row.official_url || row.completion_date)
    .map((row) => ({
      ...row,
      title: row.title || "Untitled course",
    }));
}

// Build a clean array of employment history rows from the form fields.
function buildEmploymentHistory(body) {
  const employers = toArray(body.employmentEmployer);
  const jobTitles = toArray(body.employmentJobTitle);
  const startDates = toArray(body.employmentStartDate);
  const endDates = toArray(body.employmentEndDate);

  return employers
    .map((employer, index) => ({
      employer_name: sanitizeSingleLineText(employer, 140),
      job_title: sanitizeSingleLineText(jobTitles[index], 140),
      start_date: String(startDates[index] || "").trim(),
      end_date: String(endDates[index] || "").trim(),
    }))
    .filter((row) => row.employer_name || row.job_title || row.start_date || row.end_date)
    .map((row) => ({
      ...row,
      employer_name: row.employer_name || "Unnamed employer",
    }));
}

// Convert a saved profile into form-friendly arrays so the edit page can reuse the same template.
function buildFormData(user, profile) {
  return {
    fullName: user.fullName,
    email: user.email,
    contactNumber: profile?.contact_number || "",
    location: profile?.location || "",
    professionalHeadline: profile?.professional_headline || "",
    biography: profile?.biography || "",
    linkedinUrl: profile?.linkedin_url || "",
    profileImagePath: profile?.profile_image_path || "",
    degrees:
      profile?.degrees?.length
        ? profile.degrees
        : [{ title: "", institution_name: "", official_url: "", completion_date: "" }],
    certifications:
      profile?.certifications?.length
        ? profile.certifications
        : [{ title: "", provider_name: "", official_url: "", completion_date: "" }],
    licenses:
      profile?.licenses?.length
        ? profile.licenses
        : [{ title: "", awarding_body: "", official_url: "", completion_date: "" }],
    courses:
      profile?.courses?.length
        ? profile.courses
        : [{ title: "", provider_name: "", official_url: "", completion_date: "" }],
    employmentHistory:
      profile?.employmentHistory?.length
        ? profile.employmentHistory
        : [{ employer_name: "", job_title: "", start_date: "", end_date: "" }],
  };
}

// Render the create/edit alumni profile page.
exports.showProfileForm = (req, res) => {
  // Read the existing profile for the logged-in user, if one already exists.
  const profile = AlumniProfile.getProfileByUserId(req.session.user.id);

  // Render the shared profile form view with either saved values or empty defaults.
  res.render("profile/form", {
    title: profile ? "Edit Alumni Profile" : "Create Alumni Profile",
    error: null,
    success: null,
    hasExistingProfile: Boolean(profile),
    formData: buildFormData(req.session.user, profile),
  });
};

// Show the saved alumni profile page.
exports.showProfile = (req, res) => {
  // Load the current user's profile and related section rows.
  const profile = AlumniProfile.getProfileByUserId(req.session.user.id);

  // If no profile exists yet, send the user straight to the create page.
  if (!profile) {
    return res.redirect("/alumni-profile/edit");
  }

  // Render the read-only display page.
  return res.render("profile/show", {
    title: "My Alumni Profile",
    profile,
  });
};

// Save the posted alumni profile form.
exports.saveProfile = (req, res) => {
  let existingProfile = null;
  let pageTitle = "Create Alumni Profile";
  try {
    // Read the current saved profile so we can keep the old image when no new one is uploaded.
    existingProfile = AlumniProfile.getProfileByUserId(req.session.user.id);
    pageTitle = existingProfile ? "Edit Alumni Profile" : "Create Alumni Profile";
    // Normalize the simple scalar fields.
    const contactNumber = sanitizeSingleLineText(req.body.contactNumber, 40);
    const location = sanitizeSingleLineText(req.body.location, 120);
    const professionalHeadline = sanitizeSingleLineText(req.body.professionalHeadline, 160);
    const biography = sanitizeMultilineText(req.body.biography, 2500);
    const linkedinUrl = String(req.body.linkedinUrl || "").trim();
    // Use the uploaded file path when a new image was submitted.
    const profileImagePath = req.file
      ? `/uploads/profiles/${req.file.filename}`
      : existingProfile?.profile_image_path || "";

    // Build the repeatable section arrays from the parallel form field arrays.
    const degrees = buildDegrees(req.body);
    const certifications = buildCertifications(req.body);
    const licenses = buildLicenses(req.body);
    const courses = buildCourses(req.body);
    const employmentHistory = buildEmploymentHistory(req.body);

    // If the upload middleware reported a file error, show it on the form immediately.
    if (req.uploadError) {
      return res.render("profile/form", {
        title: pageTitle,
        error: req.uploadError,
        success: null,
        hasExistingProfile: Boolean(existingProfile),
        formData: {
          ...buildFormData(req.session.user, existingProfile),
          contactNumber,
          location,
          professionalHeadline,
          biography,
          linkedinUrl,
          profileImagePath,
          degrees,
          certifications,
          licenses,
          courses,
          employmentHistory,
        },
      });
    }

    // Validate the main LinkedIn URL only when the field is filled in.
    if (!isValidOptionalUrl(linkedinUrl)) {
      return res.render("profile/form", {
        title: pageTitle,
        error: "Please enter a valid LinkedIn profile URL.",
        success: null,
        hasExistingProfile: Boolean(existingProfile),
        formData: {
          ...buildFormData(req.session.user, existingProfile),
          contactNumber,
          location,
          professionalHeadline,
          biography,
          linkedinUrl,
          profileImagePath,
          degrees,
          certifications,
          licenses,
          courses,
          employmentHistory,
        },
      });
    }

    // Validate all optional section URLs before saving.
    const invalidUrlExists = [
      ...degrees.map((row) => row.official_url),
      ...certifications.map((row) => row.official_url),
      ...licenses.map((row) => row.official_url),
      ...courses.map((row) => row.official_url),
    ].some((url) => !isValidOptionalUrl(url));

    if (invalidUrlExists) {
      return res.render("profile/form", {
        title: pageTitle,
        error: "Please enter valid URLs for LinkedIn, degrees, certifications, licences, and courses.",
        success: null,
        hasExistingProfile: Boolean(existingProfile),
        formData: {
          ...buildFormData(req.session.user, existingProfile),
          contactNumber,
          location,
          professionalHeadline,
          biography,
          linkedinUrl,
          profileImagePath,
          degrees,
          certifications,
          licenses,
          courses,
          employmentHistory,
        },
      });
    }

    // Save the main profile and all repeatable sections in one transaction.
    AlumniProfile.saveProfile(req.session.user.id, {
      contactNumber,
      location,
      professionalHeadline,
      biography,
      linkedinUrl,
      profileImagePath,
      degrees,
      certifications,
      licenses,
      courses,
      employmentHistory,
    });

    // Redirect to the read-only profile page after a successful save.
    return res.redirect("/alumni-profile");
  } catch (error) {
    // If the image upload middleware throws, show its message when possible.
    console.error(error);
    return res.render("profile/form", {
      title: pageTitle,
      error: error.message || "Something went wrong while saving the alumni profile.",
      success: null,
      hasExistingProfile: Boolean(existingProfile),
      formData: buildFormData(req.session.user, AlumniProfile.getProfileByUserId(req.session.user.id)),
    });
  }
};

// Delete the entire alumni profile when the user confirms they no longer want it stored.
exports.deleteProfile = (req, res) => {
  try {
    AlumniProfile.deleteProfileByUserId(req.session.user.id);

    return res.render("auth/message", {
      title: "Profile deleted",
      message: "Your alumni profile and all related entries were deleted successfully.",
    });
  } catch (error) {
    console.error(error);
    return res.render("auth/message", {
      title: "Delete failed",
      message: "Something went wrong while deleting the alumni profile.",
    });
  }
};
